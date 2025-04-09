Чтобы хранить логин и пароль для авторизации, добавим таблицу users, связанную с заявками:
```SQL
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  application_id INT NOT NULL,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);
```
