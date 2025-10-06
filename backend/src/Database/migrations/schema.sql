CREATE TABLE IF NOT EXISTS reactions (
                                         id INT AUTO_INCREMENT PRIMARY KEY,
                                         name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message VARCHAR(400) NOT NULL,
    rating TINYINT NOT NULL,
    created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

