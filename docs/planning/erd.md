# ERD

```text
USERS (1) ─────< TASKS >───── (1) PROJECTS
  id PK             id PK          id PK
  username          project_id FK  name
  role              assignee_id FK status
  is_active         status         start_date
                    priority        target_date
                    due_date
```

Foreign keys: `tasks.project_id → projects.id` and `tasks.assignee_id → users.id`.
Indexes are present on project status/target and task project/assignee/status/due/status for common list and dashboard queries.
