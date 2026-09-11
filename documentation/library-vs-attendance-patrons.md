# Library Vs Attendance Patrons

PANTAS now separates Library patrons from school Attendance patrons.

## Core Rule

Library activity and school Attendance activity must never share the same patron or log tables.

## Library Domain

Library patrons live in:

- `library_students`
- `library_employees`
- `library_pending_students`
- `library_pending_employees`

Library workflows include:

- OPAC and catalog
- Borrowing and circulation
- Fines
- Room reservations
- Library feedback
- Library kiosk lookup
- Library attendance / visit logs

Library visit scans write to:

- `library_attendance_logs`

## Attendance Domain

School Attendance patrons live in:

- `attendance_students`
- `attendance_employees`
- `attendance_pending_students`
- `attendance_pending_employees`

Attendance workflows include:

- School attendance scanner
- School attendance logs
- Attendance reports
- Attendance feedback
- Attendance settings

School attendance scans write to:

- `attendance_logs`

## Scanner Lookup Rules

Both scanners accept a QR code **or** an ID number typed/scanned directly.

| Scanner | Path | Resolves against | Accepts |
| --- | --- | --- | --- |
| School Attendance | `/attendance` | `attendance_students`, `attendance_employees` | QR, student ID, employee ID/number |
| Library visits | `/library/attendance/scanner` | `library_students`, `library_employees` | QR, student ID number, employee ID |

Cross-domain scans are rejected: a Library-only patron will not match on `/attendance`, and an Attendance-only patron will not match on the Library visit scanner.

## Registration

Public registration starts at:

```text
/register
```

From there:

- Library registration writes to Library pending tables.
- Attendance registration writes to Attendance pending tables.
- Approval moves pending records into the matching active domain tables.

## Mobile API

`/api/mobile` is owned by `pantas-v2.5` and uses Library-domain tables for patron profile, catalog, borrowing, room reservations, feedback, and notifications.

The mobile API does not use school Attendance patrons.
