# Database Documentation: `gplms_general`

## Overview
General-purpose library management system for cataloging and displaying materials without lending functionality. Supports multilingual content (including Greek) with admin configuration capabilities.

---

## Tables

### 1. `roles`
**Purpose**: Stores system roles for access control  
**Relationships**: 
- One-to-many with `users.role_id`

| Field       | Type         | Constraints               | Description               |
|-------------|--------------|---------------------------|---------------------------|
| role_id     | INT          | AUTO_INCREMENT PRIMARY KEY| Unique role identifier    |
| role_name   | VARCHAR(50)  | UNIQUE NOT NULL           | Name of the role          |

---

### 2. `users`
**Purpose**: Manages system user accounts and authentication  
**Relationships**: 
- Many-to-one with `roles`
- One-to-many with `library_items.added_by`
- One-to-many with `activity_logs.user_id`

| Field       | Type          | Constraints                      | Description                     |
|-------------|---------------|----------------------------------|---------------------------------|
| user_id     | INT           | AUTO_INCREMENT PRIMARY KEY       | Unique user identifier          |
| username    | VARCHAR(255)  | UNIQUE NOT NULL                  | Login username                  |
| password    | VARCHAR(255)  | NOT NULL                         | Hashed password                 |
| full_name   | VARCHAR(255)  | NOT NULL                         | User's full name                |
| email       | VARCHAR(255)  | UNIQUE NOT NULL                  | User's email address            |
| phone       | VARCHAR(20)   |                                  | Contact number                  |
| role_id     | INT           | NOT NULL FK → roles.role_id      | Associated role                 |
| created_at  | TIMESTAMP     | DEFAULT CURRENT_TIMESTAMP        | Account creation timestamp      |

---

### 3. `material_types`
**Purpose**: Defines types of library materials  
**Relationships**: 
- One-to-many with `library_items.type_id`

| Field       | Type         | Constraints               | Description               |
|-------------|--------------|---------------------------|---------------------------|
| type_id     | INT          | AUTO_INCREMENT PRIMARY KEY| Material type identifier  |
| type_name   | VARCHAR(50)  | UNIQUE NOT NULL           | Type name (e.g., Book)    |

---

### 4. `publishers`
**Purpose**: Stores publisher information  
**Relationships**: 
- One-to-many with `library_items.publisher_id`

| Field         | Type          | Constraints               | Description                 |
|---------------|---------------|---------------------------|-----------------------------|
| publisher_id  | INT           | AUTO_INCREMENT PRIMARY KEY| Unique publisher identifier |
| name          | VARCHAR(255)  | UNIQUE NOT NULL           | Publisher name             |
| contact_info  | TEXT          |                           | Contact details            |

---

### 5. `authors`
**Purpose**: Manages author information  
**Relationships**: 
- Many-to-many with `library_items` via `item_authors`

| Field       | Type          | Constraints               | Description              |
|-------------|---------------|---------------------------|--------------------------|
| author_id   | INT           | AUTO_INCREMENT PRIMARY KEY| Unique author identifier |
| name        | VARCHAR(255)  | NOT NULL                  | Author's name            |
| bio         | TEXT          |                           | Biography                |

---

### 6. `categories`
**Purpose**: Organizes materials by subject/genre  
**Relationships**: 
- One-to-many with `library_items.category_id`

| Field         | Type          | Constraints               | Description             |
|---------------|---------------|---------------------------|-------------------------|
| category_id   | INT           | AUTO_INCREMENT PRIMARY KEY| Category identifier     |
| name          | VARCHAR(255)  | UNIQUE NOT NULL           | Category name           |

---

### 7. `library_items`
**Purpose**: Core table storing all library materials  
**Relationships**: 
- Many-to-one with `material_types`
- Many-to-one with `categories`
- Many-to-one with `publishers`
- Many-to-one with `users`
- Many-to-many with `authors` via `item_authors`

| Field             | Type          | Constraints                               | Description                   |
|-------------------|---------------|-------------------------------------------|-------------------------------|
| item_id           | INT           | AUTO_INCREMENT PRIMARY KEY                | Unique item identifier        |
| title             | VARCHAR(255)  | NOT NULL                                  | Item title                    |
| type_id           | INT           | NOT NULL FK → material_types.type_id      | Material type                 |
| category_id       | INT           | FK → categories.category_id               | Category association          |
| publisher_id      | INT           | FK → publishers.publisher_id              | Publisher association         |
| language          | VARCHAR(10)   | NOT NULL DEFAULT 'EN'                     | Item language                 |
| publication_year  | YEAR          |                                           | Year published                |
| edition           | INT           |                                           | Edition number                |
| isbn              | VARCHAR(17)   | UNIQUE                                    | ISBN for books                |
| issn              | VARCHAR(9)    | UNIQUE                                    | ISSN for periodicals          |
| description       | TEXT          | CHARACTER SET utf8mb4                     | Item description              |
| added_date        | DATE          | NOT NULL DEFAULT (CURRENT_DATE)           | Date added to system          |
| added_by          | INT           | NOT NULL FK → users.user_id               | User who added item           |
| status            | ENUM          | NOT NULL DEFAULT 'available'              | Availability status           |

---

### 8. `item_authors`
**Purpose**: Junction table linking items to authors  
**Relationships**: 
- Composite PK: (item_id, author_id)
- ON DELETE CASCADE to both parent tables

| Field       | Type | Constraints                               | Description           |
|-------------|------|-------------------------------------------|-----------------------|
| item_id     | INT  | NOT NULL FK → library_items.item_id       | Associated item      |
| author_id   | INT  | NOT NULL FK → authors.author_id           | Associated author    |

---

### 9. `system_settings`
**Purpose**: Stores configurable system settings for admin panel  

| Field          | Type          | Constraints                      | Description                     |
|----------------|---------------|----------------------------------|---------------------------------|
| setting_id     | INT           | AUTO_INCREMENT PRIMARY KEY       | Setting identifier              |
| setting_key    | VARCHAR(255)  | UNIQUE NOT NULL                  | Setting name/key                |
| setting_value  | TEXT          | NOT NULL                         | Setting value                   |
| description    | TEXT          |                                  | Setting purpose                 |
| last_modified  | TIMESTAMP     | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last modification timestamp   |

---

### 10. `activity_logs`
**Purpose**: Audits user activities for security and monitoring  
**Relationships**: 
- Many-to-one with `users`

| Field         | Type          | Constraints                     | Description                |
|---------------|---------------|---------------------------------|----------------------------|
| log_id        | INT           | AUTO_INCREMENT PRIMARY KEY      | Log entry identifier       |
| user_id       | INT           | NOT NULL FK → users.user_id     | User who performed action  |
| action        | VARCHAR(50)   | NOT NULL                        | Action performed           |
| target_object | VARCHAR(255)  |                                 | Affected object            |
| details       | TEXT          |                                 | Action details             |
| ip_address    | VARCHAR(45)   |                                 | User's IP address          |
| timestamp     | TIMESTAMP     | DEFAULT CURRENT_TIMESTAMP       | Action timestamp           |

---

## Sample Data
```sql
-- Roles
INSERT INTO roles (role_name) VALUES 
('Administrator'), 
('Librarian');

-- Admin User
INSERT INTO users (username, password, full_name, email, role_id)
VALUES ('admin', '12345', 'System Admin', 'admin@library.com', 1);

-- Material Types
INSERT INTO material_types (type_name) VALUES 
('Book'), ('Magazine'), ('Newspaper'), ('Journal'), ('Manuscript'),
('Βιβλίο'), ('Περιοδικό'), ('Εφημερίδα');

-- Settings
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES 
('library_name', 'City Central Library', 'Display name for the library'),
('max_items_per_page', '25', 'Pagination limit for search results'),
('enable_user_registration', '1', 'Allow new user registrations');

-- Greek Data
INSERT INTO publishers (name) VALUES ('Εκδόσεις Πατάκη');
INSERT INTO authors (name) VALUES ('Νίκος Καζαντζάκης');
INSERT INTO library_items (title, type_id, publisher_id, language, publication_year, added_by, status)
VALUES ('Ο Αλέξης Ζορμπάς', 
        (SELECT type_id FROM material_types WHERE type_name = 'Βιβλίο'),
        (SELECT publisher_id FROM publishers WHERE name = 'Εκδόσεις Πατάκη'),
        'GR', 1946, 1, 'available');
INSERT INTO item_authors VALUES (LAST_INSERT_ID(), (SELECT author_id FROM authors WHERE name = 'Νίκος Καζαντζάκης'));
```

### Analytics Queries

> 1. User-Role Distribution

```sql

SELECT r.role_name, COUNT(u.user_id) AS user_count
FROM roles r
LEFT JOIN users u ON r.role_id = u.role_id
GROUP BY r.role_name;
```

> 2. Material Type Distribution

```sql

SELECT mt.type_name, COUNT(li.item_id) AS item_count
FROM material_types mt
LEFT JOIN library_items li ON mt.type_id = li.type_id
GROUP BY mt.type_name
ORDER BY item_count DESC;
```

> 3. Language Usage Statistics

```sql

SELECT 
    language,
    COUNT(*) AS total_items,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM library_items), 2) AS percentage
FROM library_items
GROUP BY language;
```

?4. Recent Admin Activities

```sql

SELECT u.username, al.action, al.target_object, al.timestamp 
FROM activity_logs al
JOIN users u ON al.user_id = u.user_id
WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'Administrator')
ORDER BY al.timestamp DESC
LIMIT 10;
```

> 5. Publisher Catalog Size

```sql

SELECT p.name AS publisher, COUNT(li.item_id) AS items_published
FROM publishers p
LEFT JOIN library_items li ON p.publisher_id = li.publisher_id
GROUP BY p.name
ORDER BY items_published DESC;
```

### Entity Relationship Diagram

Diagram
```mermaid

erDiagram
    roles ||--o{ users : "1:N"
    users ||--o{ library_items : "1:N"
    users ||--o{ activity_logs : "1:N"
    material_types ||--o{ library_items : "1:N"
    publishers ||--o{ library_items : "1:N"
    categories ||--o{ library_items : "1:N"
    library_items }o--o{ authors : "N:M via item_authors"
    system_settings }|..|| : "Standalone Config"
```

    Note: Run SHOW CREATE TABLE commands for complete constraint details. Database last modified: 2025-06-18
