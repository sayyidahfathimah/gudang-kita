# Operability contract
Owner: Delivery Platform. Critical journey: login, create purchase order, complete sales order. Dependency: MySQL.

SLI: successful HTTP requests / valid HTTP requests. SLO: 99.9% successful requests over 30 days. Error budget: 0.1%.

Ports: HTTP 8080. Config and secrets: environment or Kubernetes Secret. Health: `/health/live`, `/health/ready`, `/health/startup`. Metrics: `/metrics`. Rollback owner: release operator using Helm rollback.
