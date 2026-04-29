### BIG BROTHER (Attendance Tool)
A tool to help teachers and students keep track of their participation and attendance.

### Quick Start
- Go to db.php and change 3308 to the port you're using for MySql in xampp
- Go to phpmyadmin via MySql's admin button in xampp and go to the SQL tab at the top
- Copy and paste the contents of sql\schema.sql into the text area and press the "Go" button
    - You need to ensure your database has the exact infrastructure as outlined in the file
    - attendence, enrollments, sessions, subjects, and users tables need to have the proper coloumns defined
- If you've done all this, you can start up your xampp server and begin using the web app

### Features

#### Teachers
- View the attendance stats for all students enrolled in their classes
- Create classes which will be displayed to students
- View their classes, add a session, and appropriately mark their enrolled students' attendances
- View the details of specific enrolled students and their attendance percentage
- Message students via email

#### Students
- View your total attendance percentage in your enrolled classes
- Enroll in any available courses provided by professors
- Message teachers via email

You can also toggle themes. And there's a cool interactive eye.