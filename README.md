Чтобы хранить логин и пароль для авторизации, добавим таблицу users, связанную с заявками:
```SQL
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  application_id INT NOT NULL,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
);

-- Добавление администратора по умолчанию (логин: admin, пароль: 123)
INSERT INTO admins (login, password_hash) VALUES ('admin', '202cb962ac59075b964b07152d234b70');

```
