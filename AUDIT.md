# Accessing — Code Audit

## Аудит — 2026-07-10

### Критические баги

1. **Второй фактор (TOTP/recovery codes) не защищён rate-limiter'ом — можно брутфорсить 2FA после утечки пароля.**
   `src/Service/SecondFactor/AccessSecondFactorService.php:107` (`verifyChallenge`) вызывается из `src/Service/Http/Api/Access/ApiAccessFlowService.php:254` и `src/Service/Http/Access/AccessSecurityFlowService.php:160` без какого-либо `RateLimiterFactory`. Единственный реально подключённый лимитер в приложении — `accessingSignInLimiter` (`AccessAuthenticationService.php:35`). Конфиг `config/packages/accessing_rate_limiter.yaml` определяет ещё 5 лимитеров (`accessing_sign_up`, `accessing_recovery`, `accessing_forgot_password`, `accessing_verification`, `accessing_verification_resend`), но grep по `src/` показывает, что ни один из них никогда не инжектируется и не используется — это "мёртвый" конфиг. В сумме: атакующий, узнавший пароль пользователя (утечка, фишинг), может неограниченно перебирать 6-значный TOTP-код или один из 8 recovery-кодов без каких-либо задержек/блокировок.

2. **Счётчик попыток ввода кода верификации не сохраняется — защита от брутфорса не работает.**
   `src/Entity/AccessVerificationChallengeEntity.php:49`: `private int $attemptCount = 0;` — поле объявлено **без** `#[ORM\Column]`, то есть при флаше в БД не пишется и не читается. `registerAttempt()` (строка 217-222) инкрементирует значение, которое живёт только в рамках одного PHP-запроса и нигде не сравнивается ни с каким лимитом (`AccessVerificationChallengeService::consumeChallenge`, строки 221-241, не содержит проверки `attemptCount` против порога). Итог: код email/phone-верификации и password recovery (6 цифр, 1 000 000 комбинаций) можно перебирать без какой-либо блокировки — единственная защита — TTL кода (10-15 минут), что не спасает от автоматизированного перебора.

3. **Несовпадение типа события регистрации ломает корреляцию security-событий.**
   `src/Service/Access/AccessRegistrationService.php:48-51` записывает событие через `AccessSecurityEventRecorderInterface::record('user.registered', ...)` — сырой строковый тип `'user.registered'`. Все остальные места кода используют enum `AccessSecurityEventType` (`src/ValueObject/AccessSecurityEventType.php:10`), где соответствующее значение — `UserRegistered = 'user_registered'` (подчёркивание, не точка). Это два независимых способа записи одного и того же типа события (`AccessSecurityEventRecorder::record(string, ...)` в `src/Recorder/SecurityEvent/AccessSecurityEventRecorder.php:23` и `AccessSecurityEventService::record(AccessSecurityEventType, ...)` в `src/Service/SecurityEvent/AccessSecurityEventService.php:24`), и регистрация — единственное место, использующее первый вариант. Любая выборка/отчёт по `AccessSecurityEventType::UserRegistered` (например, `AccessReportSecurityCommand`) не найдёт события регистрации, так как в БД хранится другая строка.

### Потенциальные баги

4. **`AccessEntity::isLocked()` — геттер с побочным эффектом (мутация состояния).**
   `src/Entity/AccessEntity.php:383-390`: метод `isLocked()` при истёкшем `lockedUntil` сам вызывает `$this->unlock()`, изменяя состояние сущности (сбрасывает `locked`, `lockedUntil`, `failedLoginCount`) прямо во время "чтения". Нарушение command-query separation: вызывающий код, ожидающий чистого чтения, незаметно получает изменённую (но не обязательно сохранённую через `flush()`) сущность. В `AccessAuthenticationService.php:67-69` это приводит к запутанной двойной логике: `if ($user->getLockedUntil() instanceof \DateTimeImmutable && !$user->isLocked()) { $user->unlock(); }` — при истёкшей блокировке `isLocked()` уже сам разблокировал пользователя, и внешний `unlock()` вызывается повторно избыточно; логику трудно читать и легко сломать при рефакторинге.

5. **Fail-open с захардкоженным TOTP-секретом по умолчанию.**
   `src/Service/SecondFactor/AccessSecondFactorService.php:176-182` (`nonEmptySecret`): если у пользователя пустой `totpSecret`, метод молча подставляет константу `'ACCESSING-DEFAULT-SECRET'` вместо того, чтобы бросить исключение. Если из-за какого-то бага/миграции секрет окажется пустым у нескольких пользователей, TOTP-код для всех них будет вычисляться от одного публично известного (в исходниках) секрета — по сути 2FA превращается в security theatre для таких аккаунтов.

6. **Возможен user-enumeration через тайминг при входе.**
   `src/Service/Access/AccessAuthenticationService.php:53-65` возвращает одинаковое сообщение `'Invalid sign in credentials.'` и для несуществующего email, и для неверного пароля — это хорошо. Но путь "email не найден" не вызывает `password_hasher` вовсе, тогда как путь "email найден, пароль неверный" вызывает `verifyPassword()` → дорогой bcrypt/argon2. Разница во времени ответа позволяет отличить существующий email от несуществующего.

7. **Номер телефона записывается в сущность до подтверждения кода.**
   `src/Service/Verification/AccessVerificationChallengeService.php:80`: `issuePhoneVerification()` вызывает `$user->changePhoneNumber($phoneNumber)` немедленно, до того как пользователь ввёл код подтверждения. Если верификация так и не будет завершена (пользователь передумал/ошибся номером), в `AccessEntity::$phoneNumber` всё равно останется непроверенный номер, который в остальном коде (`isPhoneVerified()`) отделён от факта верификации, но сам номер уже "перезаписан" — путаница между "текущий указанный номер" и "верифицированный номер".

8. **`AccessPhoneVerificationGatewayService` полагается на identity-проверку для исключения себя из своей же коллекции провайдеров.**
   `src/Service/Vendor/AccessPhoneVerificationGatewayService.php:28-30`: `foreach ($this->providers as $provider) { if ($provider === $this) { continue; } ... }`. Это указывает на то, что данный сервис тегирован в тот же DI-tag, что и обычные провайдеры, и попадает в свой собственный `iterable`. Хрупкая конструкция — при рефакторинге DI-тегов (например, смене имени тега) этот self-reference guard легко забыть, что приведёт к бесконечной рекурсии.

### Риски/безопасность

9. **`APP_SECRET=change-me` закоммичен в git и не в `.gitignore`.**
   `.env:2` содержит `APP_SECRET=change-me`. `.gitignore` не исключает `.env` (исключены только `.env.test`-подобные паттерны — которых там даже нет), файл реально отслеживается (`git ls-files` подтверждает `.env` в репозитории, история — `git log -- .env` показывает 2 коммита). `APP_SECRET` используется, среди прочего, для HMAC-подписи кодов верификации (`AccessVerificationChallengeService::hashCode`, строка 245) и recovery-кодов (`AccessSecondFactorService::hashRecoveryCode`, строка 173). Значение по умолчанию `change-me`, если не переопределено в проде через `.env.local`/переменные окружения, полностью компрометирует HMAC-подписи кодов.

10. **5 из 6 сконфигурированных rate limiter'ов не подключены нигде в коде** (см. пункт 1). Помимо брутфорса 2FA, это открывает: неограниченную рассылку email/SMS с кодами верификации (`accessing_verification_resend` не используется — возможность SMS-bombing/спам-рассылки на произвольный номер через `issuePhoneVerification`, которая принимает номер от клиента без проверки владения), неограниченные попытки password recovery и sign-up (потенциал для automation/спама регистраций).

11. **`composer.lock` в `.gitignore`** (`.gitignore` строка "composer.lock"). Для приложения с зафиксированными критичными security-зависимостями (`symfony/security-bundle`, `symfony/password-hasher`, `scheb/2fa-bundle`) отсутствие закоммиченного lock-файла означает невоспроизводимые сборки — CI/прод и локальная машина разработчика могут получить разные транзитивные версии пакетов, включая версии с потенциальными уязвимостями, без явного контроля.

12. **Ручная сериализация токена безопасности напрямую в сессию.**
    `src/Service/Access/AccessAuthenticationService.php:171`: `$session->set('_security_'.self::FIREWALL_NAME, serialize($token));` — код вручную пишет в сессию по внутреннему ключевому формату Symfony Security (`_security_<firewall>`), обходя штатные механизмы аутентификации (`UserAuthenticatorInterface`/`SecurityRequestAttributes`). Это скрытая зависимость от деталей реализации Symfony Security, которая может измениться в минорных версиях фреймворка и тихо сломать вход в систему.

### Boilerplate/дублирование

13. Дублирование HMAC-хэширования кодов: `AccessVerificationChallengeService::hashCode()` (строка 243-246) и `AccessSecondFactorService::hashRecoveryCode()` (строка 171-174) — идентичная логика `hash_hmac('sha256', $code, $this->appSecret)`, продублированная в двух сервисах вместо общего value object/сервиса.

14. Две параллельные абстракции для записи security-событий: `AccessSecurityEventRecorderInterface` (`src/Recorder/SecurityEvent/AccessSecurityEventRecorder.php`) и `AccessSecurityEventServiceInterface` (`src/Service/SecurityEvent/AccessSecurityEventService.php`) выполняют одну и ту же задачу с разными сигнатурами (см. пункт 3) — явное дублирование ответственности.

15. Generic CRUD-скелет поверх auth-сущности: `src/Service/Http/Access/` содержит по отдельному одно-методному сервису на каждое действие generic CRUD-паттерна — `AccessArchiveService`, `AccessBulkService`, `AccessDuplicateService`, `AccessExportService`, `AccessImportService`, `AccessRestoreService` и соответствующие `Form/Access/AccessArchiveType.php`, `AccessBulkType.php`, `AccessDuplicateType.php`, `AccessExportType.php`, `AccessImportType.php`, `AccessRestoreType.php`. Само имя `src/Service/Http/Access/AccessCrudSkeletonException.php` прямо признаёт, что это шаблонный скелет, сгенерированный по общему паттерну, а не специально спроектированный для сущности учётных записей.

### Overengineering

16. Импорт/экспорт/дублирование/архивация/bulk-операции над `AccessEntity` (учётные записи пользователей) — это функциональность, типичная для generic admin-CRUD-генератора, а не для authentication-бандла. Массовый экспорт/импорт пользовательских аккаунтов без отдельного слоя compliance/audit контролей (кроме generic security event log) — избыточная и потенциально небезопасная поверхность API для домена "доступ/аутентификация" (см. пункт 15).

17. `AccessPhoneVerificationGatewayService` реализует паттерн "gateway поверх iterable провайдеров с сопоставлением по имени и самоисключением" (см. пункт 8) там, где для единственного активного провайдера (`ACCESSING_PHONE_VERIFICATION_PROVIDER=fake` в `.env`) было бы достаточно простого service locator/фабрики по имени без самоссылки.

### Нарушения SOLID

18. **SRP** — `src/Service/Http/Api/Access/ApiAccessFlowService.php` (514 строк) объединяет в одном классе: 11 публичных "эндпоинтов" (`signIn`, `register`, `logout`, `session`, `resendVerification`, `confirmVerification`, `challengeSecondFactor`, `verifySecondFactor`, `requestRecovery`, `resetRecovery`), парсинг/декодирование JSON-запроса (`decodeJsonPayload`, `stringField`, `readEmailRequest`, `readCodeRequest`, `readSignInRequest`, `readRegisterRequest`) и построение ответов (`unauthorizedResponse`, `invalidRequestResponse`, `sessionFromUser`, `errorCodeForSignInResult`, `statusCodeForSignInResult`). Это классический god-class: контроллер + валидатор запроса + responder в одном месте, при том что для этого уже существуют отдельные `Dto/Api/Access/*` и `Responder/Api/Access/ApiAccessJsonResponder`.

19. **SRP** — `src/Entity/AccessEntity.php` (490 строк) совмещает идентификацию пользователя, хранение пароля, TOTP/2FA-конфигурацию, состояние блокировки/rate-limit (`locked`, `lockedUntil`, `failedLoginCount`) и владение коллекциями recovery-кодов/сессий/challenge — классический god-entity с более чем одной причиной для изменения (изменение политики блокировки, изменение схемы 2FA, изменение схемы сессий — всё меняет один класс).

20. **DIP/ISP** — `AccessRegistrationService` (`src/Service/Access/AccessRegistrationService.php:23`) зависит от `AccessSecurityEventRecorderInterface`, тогда как весь остальной код (`AccessAuthenticationService`, `AccessRecoveryService`, `AccessVerificationChallengeService`, `AccessSecondFactorService`, `AccessSessionService`) зависит от `AccessSecurityEventServiceInterface` для той же задачи — непоследовательные абстракции для одного и того же порта (см. пункты 3, 14).

21. **OCP** — `AccessPhoneVerificationGatewayService` реализует тот же интерфейс, что и оборачиваемые им провайдеры, и добавление нового провайдера в тег DI требует помнить про self-reference guard (`$provider === $this`) — расширение списка провайдеров рискует сломать существующий gateway без явного контракта, что новый провайдер не должен ссылаться сам на себя.

### Хрупкость архитектуры

22. Дублирующиеся и частично несогласованные абстракции логирования security-событий (пункты 3, 14, 20) — нет единой точки входа для аудита событий, что уже привело к реальному дефекту (несовпадающий тип события при регистрации).

23. "Мёртвая" конфигурация rate limiter'ов (5 из 6, см. пункт 1) — конфигурация выглядит так, будто защита есть, но фактически отсутствует; это хрупкость на границе между конфигурацией и кодом, которую легко не заметить при код-ревью, ориентируясь только на `config/packages/accessing_rate_limiter.yaml`.

24. Прямая работа с внутренним форматом сессии Symfony Security (пункт 12) — скрытая зависимость от деталей реализации фреймворка вместо использования публичного контракта аутентификации.

### Хрупкость методов

25. `AccessEntity::isLocked()` — геттер с побочным эффектом (пункт 4).

26. `ApiAccessFlowService` — сигнатуры вида `private function readEmailRequest(Request $request, array &$fieldErrors): string`, `readCodeRequest(..., array &$fieldErrors)`, `readSignInRequest(..., array &$fieldErrors)`, `readRegisterRequest(..., array &$fieldErrors)`, `decodeJsonPayload(..., array &$fieldErrors)`, `stringField(..., array &$fieldErrors)` (строки 297-401) — повсеместное использование параметров по ссылке (`&$fieldErrors`) для накопления ошибок вместо возврата структурированного результата (например, DTO с массивом ошибок) — неявные побочные эффекты, трудно тестируемый и читаемый код.

27. `AccessVerificationChallengeEntity::registerAttempt()` (строки 217-222) увеличивает поле, которое нигде не сохраняется и не читается (пункт 2) — метод, который выглядит значимым, но фактически ничего не делает (dead code, вводящее в заблуждение при чтении кода).

### Устаревшие зависимости

28. Версии основных зависимостей (`composer.lock`) актуальны и не EOL: PHP `^8.4` (установлен 8.4.13), `symfony/*` `v8.1.0`, `doctrine/orm` `3.6.7` — явных признаков устаревания не обнаружено.

29. Замечание по стилю пиннинга: часть пакетов в `composer.json` зафиксирована точной версией без caret (`"friendsofphp/php-cs-fixer": "3.94"`, `"scheb/2fa-bundle": "8.5"`, `"scheb/2fa-totp": "8.5"`), тогда как остальные используют `^`-диапазоны. Это не проблема устаревания, но несогласованность стратегии версионирования усложняет получение патч-обновлений (в т.ч. security-патчей) для этих пакетов через `composer update`.

30. `composer.lock` не версионируется (см. пункт 11) — это само по себе риск для дальнейшего контроля версий зависимостей, отдельно от их текущей актуальности.

### Итог — топ-5 приоритетов

1. **Добавить rate limiting на верификацию второго фактора (`AccessSecondFactorService::verifyChallenge`)** — сейчас TOTP/recovery-коды можно перебирать без ограничений; это самый серьёзный риск обхода аутентификации.
2. **Включить реально используемые rate limiter'ы** для sign-up, password recovery, verification/resend — конфигурация уже описана в `config/packages/accessing_rate_limiter.yaml`, но не подключена к сервисам (5 из 6 лимитеров мертвы).
3. **Убрать `APP_SECRET=change-me` из `.env` и из индекса git**, добавить `.env` (или как минимум `.env.local`) в `.gitignore`, обеспечить обязательную переопределяемую переменную в проде — секрет используется для подписи кодов верификации.
4. **Исправить несовпадение типа события регистрации** (`'user.registered'` vs enum `UserRegistered = 'user_registered'`) и объединить `AccessSecurityEventRecorderInterface`/`AccessSecurityEventServiceInterface` в одну абстракцию.
5. **Реализовать реальное сохранение и проверку `attemptCount`** в `AccessVerificationChallengeEntity`, либо убрать `registerAttempt()` как вводящий в заблуждение мёртвый код, и заменить на явную блокировку challenge после N неверных попыток.

Дополнительно стоит зафиксировать `composer.lock` в git и разгрузить `ApiAccessFlowService`/`AccessEntity` от лишних ответственностей (см. п. 18-19), а также пересмотреть необходимость generic CRUD-функционала (Archive/Bulk/Duplicate/Export/Import/Restore) над сущностью учётных записей.
