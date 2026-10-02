# Runbook: service unavailable
1. Confirm impact with `gudang_kita_database_up` and HTTP health checks. Alertmanager is available at `http://localhost:9093` and records firing/resolved database alerts through the application webhook.
2. Check rollout, Pod events, readiness, and structured JSON logs; do not log credentials.
3. If release caused the issue, run `helm rollback gudang-kita <revision> -n gudang-kita`.
4. Verify readiness, metrics, and the login journey. Record timeline and corrective action owner.
