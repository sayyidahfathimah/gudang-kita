# Test Scenarios

| ID | Scenario | Expected |
|---|---|---|
| T01 | Login valid Admin | Dashboard Admin |
| T02 | Login wrong password | Generic safe error |
| T03 | Inactive user login | Rejected |
| T04 | Member opens Users | 403 |
| T05 | Member changes another user's task | 403 |
| T06 | Project target < start | Validation error |
| T07 | Task due date outside project | Validation error |
| T08 | Task list pagination | 10 rows/page |
| T09 | Task search + filter + page 2 | Filters remain active |
| T10 | Missing route/data | 404 |
