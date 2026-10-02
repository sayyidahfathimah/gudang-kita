# Deployment validation

Run `docker compose -f docker-compose.yml -f docker-compose.observability.yml up -d` for Prometheus (`:9090`) and Grafana (`:3000`).

Before cluster deployment: replace the image digest and create `gudang-kita-runtime` from the external secret source. Render with `helm template gudang-kita helm/gudang-kita -n gudang-kita`; deploy with `helm upgrade --install --atomic --wait gudang-kita helm/gudang-kita -n gudang-kita`.

Rollback: `helm rollback gudang-kita <revision> -n gudang-kita --wait`. Validate pods, endpoints, readiness, `/metrics`, login, and the purchase/sales journeys. Record timestamps and result in the incident log.
