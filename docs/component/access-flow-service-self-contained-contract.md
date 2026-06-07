# Access Flow Service Self-Contained Contract

The Access form-processing flows live in `src/Service/Http/Access/`.

These services are the canonical targets for the Cruding/front-controller output. They must not require callers to pass the business collaborators that Symfony can inject through the container.

## Rule

Flow service public methods may accept request/runtime input, such as `Request` or a route token. They must not expose page factories, responders, repositories, recovery services, verification services, credential services, session services, or registration/authentication services as public method arguments.

Current flow services:

- `AccessSecurityFlowService`
- `AccessResetPasswordFlowService`
- `AccessSurfaceFlowService`

The legacy `Builder` classes may remain temporarily as thin compatibility delegates, but their delegated calls must target self-contained service methods.
