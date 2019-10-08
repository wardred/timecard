/****************************************************************************
 * Free Geek Timecard Schema                                                *
 * Designed for MySQL                                                       *
 *                                                                          *
 * DO NOT RUN THIS ON A SYSTEM WITH DATA!!!                                 *
 *                                                                          *
 * This will DROP ALL TABLES to create an empty schema!                     *
 *                                                                          *
 * It may be used to create empty databases for test purposes or to         *
 * see what FreeGeek's DB schema looks like.                                *
 * It was used to create the initial empty schema for FreeGeek.             *
 ****************************************************************************/

DROP TABLE IF EXISTS timecards;
DROP TABLE IF EXISTS role_to_user;
DROP TABLE IF EXISTS bans;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
	id         INT NOT NULL UNIQUE AUTO_INCREMENT,
	first_name VARCHAR(255) NOT NULL,
  last_name  VARCHAR(255),
  username   VARCHAR(255) NOT NULL UNIQUE,
  password   VARCHAR(512),
  phone      BIGINT,
  email      VARCHAR(255),
  orig_id    VARCHAR(255) UNIQUE,
  photo_filename VARCHAR(255),
  active     BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY(id),
  KEY(first_name),
  KEY(last_name),
  KEY(username),
	KEY(orig_id)
);

DROP TABLE IF EXISTS jobs;
CREATE TABLE jobs (
	id          INT NOT NULL UNIQUE AUTO_INCREMENT,
  name        VARCHAR(255) NOT NULL UNIQUE,
  description VARCHAR(255),
  active      BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY(id),
  KEY(name)
);

DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
	id          INT NOT NULL UNIQUE AUTO_INCREMENT,
  name        VARCHAR(255) NOT NULL UNIQUE,
  description VARCHAR(255),
  active      BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY(id),
  KEY(name)
);

CREATE TABLE timecards(
  id          INT NOT NULL UNIQUE AUTO_INCREMENT,
  time_in     DATETIME NOT NULL,
  time_out    DATETIME,
  user_id     INT NOT NULL,
  job_id      INT NOT NULL,
  role_id     INT NOT NULL,
  PRIMARY KEY (id),
  KEY (time_in),
  KEY (time_out),
  KEY (user_id),
  KEY (job_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (job_id)  REFERENCES jobs(id),
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE role_to_user(
	role_id   INT NOT NULL,
  user_id   INT NOT NULL,
  main      BOOLEAN NOT NULL,
  KEY (role_id),
  KEY (user_id),
  FOREIGN KEY (role_id) REFERENCES roles(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE bans(
	id          INT NOT NULL UNIQUE AUTO_INCREMENT,
  ban_name    VARCHAR(255) UNIQUE NOT NULL,
  details     VARCHAR(512),
  start       DATETIME NOT NULL,
  end         DATETIME,
  user_id   INT NOT NULL,
  PRIMARY KEY(id),
  KEY(ban_name),
  key(start),
  key(end),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

/****************************************************************************
 * The INSERT statements below are to create default jobs, roles, etc. for  *
 * FreeGeek.                                                                *
 ****************************************************************************/
INSERT INTO jobs(name, description)
VALUES
  ('Build','Build desktops, laptops, etc.'),
  ('Housekeeping','Help clean up FreeGeek.'),
  ('Test','Test RAM, HDs, etc.'),
  ('Learning','Somebody getting shown the ropes at FreeGeek.'),
  ('Imported','Imported Hours from Old Sign In System');

INSERT INTO roles(name, description)
VALUES
  ('Trainee','A user who hasn\'t been promoted by an admin.'),
  ('Volunteer','A new or regular at FreeGeek who has not been assigned another role.'),
  ('Employee','A FreeGeek employee.'),
  ('Regular','Somebody who volunteers regularly.'),
  ('Board','A member of the FreeGeek board.'),
  ('Dev','Somebody who programs for FreeGeek.'),
  ('Sysadmin','Somebody who is responsible for FreeGeek\'s IT infrastructure.'),
  ('Timecard Admin','A timecard admin.');
