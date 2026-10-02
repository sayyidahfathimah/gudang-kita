# Technical Decisions

1. **Native PHP OOP** is retained to match the required training stack.
2. **Repository + Service/Validation separation** is used to avoid a single large PHP file.
3. **PDO prepared statements** are used for all user-influenced queries.
4. **Server-side authorization** is the source of truth; frontend controls are convenience only.
5. **Query-string pagination** is used for task lists so search/filter/sort parameters can remain active across pages.
6. **MySQL 8 + Docker Compose** is the official evaluation environment.
