CREATE TABLE test.company (
	id BIGINT NULL,
	name varchar(255) NULL,
	address varchar(100) NULL
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8
COLLATE=utf8_general_ci;

CREATE TABLE test.`user` (
	id BIGINT NULL,
	first_name varchar(100) NULL,
	last_name varchar(100) NULL,
	email varchar(100) NULL,
	account varchar(100) NULL,
	company_id BIGINT NULL
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8
COLLATE=utf8_general_ci;

CREATE TABLE test.company_budget (
	id BIGINT NULL,
	company_id BIGINT NULL,
	amount DECIMAL(19,2) NULL
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8
COLLATE=utf8_general_ci;

CREATE TABLE test.`transaction` (
	id BIGINT NULL,
	`type` varchar(100) NULL,
	user_id BIGINT NULL,
	amount DECIMAL(19,2) NULL,
	`date` TIMESTAMP NULL
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8
COLLATE=utf8_general_ci;
