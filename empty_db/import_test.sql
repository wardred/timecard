
select id from job where name = 'Imported';
select id from roles where name = 'Volunteer';
/*
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
 */

select old_user.id,
  split_part(old_partner.name, ' ', 1) as first_name,
  split_part(old_partner.name, ' ', 2) as last_name,
  old_user.login as username,
  old_user.password as password,
  old_user.active as phone,
  old_partner.email as email
from res_users old_user
  join res_partner old_partner on old_user.partner_id = old_partner.id;

/*
  id          INT NOT NULL UNIQUE AUTO_INCREMENT,
  time_in     DATETIME NOT NULL,
  time_out    DATETIME,
  user_id     INT NOT NULL,
  job_id      INT NOT NULL,
  role_id     INT NOT NULL,
 */


select h1.name as time_in,
       h2.name as time_out ,
       old_user.id as user_id,
       4 as job_id,
       2 as role_id
from hr_attendance h1
  join hr_attendance h2 on h2.sheet_id = h1.sheet_id and h2.id != h1.id and h1.action = 'sign_in'
  join hr_timesheet_sheet_sheet sheet on h2.sheet_id = sheet.id
  join hr_employee hr_employee on sheet.employee_id = hr_employee.id
  join resource_resource resource on resource.id = hr_employee.resource_id
  join res_users old_user on old_user.id = resource.user_id
where h1.action = 'sign_in';

