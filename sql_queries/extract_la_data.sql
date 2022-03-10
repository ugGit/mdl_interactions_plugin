
/*
 * IMPORTANT: 
 * In this example the courses of interest have variables 2,3, and 4. 
 * Replace these ids before executing using Find & Replace.
 */

--------------------------------------------------------------------------------

/* 
 * Define a random value to be used when hashing the data.
 */
SET @randomVal = RAND(); 

--------------------------------------------------------------------------------

/*
 * Create a view to simplify future queries of users
 * enrolled with assigned roles in courses listed.
 */
DROP VIEW IF EXISTS bitnami_moodle.relevant_user_enrollments;
CREATE VIEW bitnami_moodle.relevant_user_enrollments
AS	
SELECT ra.userid,
	GROUP_CONCAT(c.id SEPARATOR ', ') AS courses, 
	GROUP_CONCAT(r.shortname SEPARATOR ', ') AS roles
FROM bitnami_moodle.mdl_course c 
	LEFT OUTER JOIN bitnami_moodle.mdl_context cx ON c.id = cx.instanceid 
	LEFT OUTER JOIN bitnami_moodle.mdl_role_assignments ra ON cx.id = ra.contextid
	LEFT OUTER JOIN bitnami_moodle.mdl_role r on r.id = ra.roleid
WHERE c.id IN (2,3,4) > 0 AND cx.contextlevel = 50
GROUP BY ra.userid;

--------------------------------------------------------------------------------

/*
 * Get log on system and course level for concerned users.
 */
SELECT log.action, log.target, log.crud, 
	log.contextlevel, log.edulevel, log.eventname, 
	SHA2(log.userid + @randomVal, 224) AS userid,
	ue.roles AS userrole, ue.courses AS usercoursenrollments, 
	SHA2(log.relateduserid + @randomVal, 224) AS relateduserid,
	rue.roles AS relateduserrole, rue.courses AS relatedusercoursenrollments, 
	log.courseid, log.timecreated 
FROM bitnami_moodle.mdl_logstore_standard_log log
	LEFT OUTER JOIN bitnami_moodle.relevant_user_enrollments ue ON ue.userid = log.userid
	LEFT OUTER JOIN bitnami_moodle.relevant_user_enrollments rue ON rue.userid = log.relateduserid
WHERE log.userid IN (SELECT userid FROM bitnami_moodle.relevant_user_enrollments)
	AND
	(log.courseid IN (0,1) OR log.courseid IN (2,3,4))
INTO OUTFILE 'extracted_log.csv'
	FIELDS TERMINATED BY ','
	ENCLOSED BY '"'
	LINES TERMINATED BY '\n';
	
--------------------------------------------------------------------------------

/*
 * Get course grades for users.
 */
SELECT c.id AS courseid, SHA2(gg.userid + @randomVal, 224) AS userid,
	gg.finalgrade, gg.rawgrademax, gg.rawgrademin, gg.rawgrade, gg.timemodified
FROM bitnami_moodle.mdl_grade_items AS gi
	INNER JOIN bitnami_moodle.mdl_course c ON c.id = gi.courseid
	LEFT JOIN bitnami_moodle.mdl_grade_grades AS gg ON gg.itemid = gi.id
	INNER JOIN bitnami_moodle.mdl_user AS mu ON gg.userid = mu.id
WHERE gi.itemtype = 'course' AND c.id IN (2,3,4)
INTO OUTFILE 'extracted_grades.csv'
	FIELDS TERMINATED BY ','
	ENCLOSED BY '"'
	LINES TERMINATED BY '\n';

--------------------------------------------------------------------------------

/*
 * Clean up auxiliary views.
 */
DROP VIEW bitnami_moodle.relevant_user_enrollments;
