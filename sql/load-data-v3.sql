-- MySQL Script to load test data based on the BITS SIG v3 into the 
-- CSI RegQ database
-- File paths used for the CSV files should be modified for the building
-- system

-- use regq;

-- grant all on regq.* to 'regq'@'localhost' identified by 'niR7zK96';
  
truncate instrument;
truncate instance;
truncate tab;
truncate section;
truncate question;
truncate questionType;
truncate questionPrompt;
truncate dbUser;


insert into instrument values
  (2, 'CSI SIG', '3.0', null);
  
insert into instance values
  (2, 1, 't', 'Master response', '2007-11-01');

-- questionType for free-form or (HTML) rich text responses.
insert into questionType (questionTypeID, format, maxLength) values
  (1,"T",null);

-- questionType for 3-valued logic responses Yes/No/NA
insert into questionType (questionTypeID, format, maxLength) values
  (2,"S",3);

-- questionPrompt(s) for Yes/No/NA responses
insert into questionPrompt (questionTypeID, value) values
  (2,"Yes");
insert into questionPrompt (questionTypeID, value) values
  (2,"No");
insert into questionPrompt (questionTypeID, value) values
  (2,"N/A");
  
-- default admin user (admin/admin)
insert into dbUser (dbUserID, dbUserName, dbUserPW, dbUserFullName) values
  (1, 'admin', 'Dd29Ji4Hf0a5054c9f756ecb19556e961ce94c150e3f81fe', 'Administrator');



-- Load tabs from a CSV text file
-- Current version of mySQL won't take the "LOCAL" infile option,
-- so edit the following line to give an absoulte pathname to the file
load data infile '~/csi-sig/testdata/sig-v3-tabs.csv' 
  into table tab
  fields terminated by ','
  enclosed by '"'
  lines terminated by '\n'
  ;
  
-- Load business info sections from a CSV text file
-- Current version of mySQL won't take the "LOCAL" infile option,
-- so edit the following line to give an absoulte pathname to the file
load data infile '~/csi-sig/testdata/sig-v3-2-sections.csv' 
  into table section
  fields terminated by ','
  enclosed by '"'
  lines terminated by '\n'
  ;

-- Load business info sections from a CSV text file
-- Current version of mySQL won't take the "LOCAL" infile option,
-- so edit the following line to give an absoulte pathname to the file
load data infile '~/csi-sig/testdata/test-v3-2-business-info.csv'
  into table question
  fields terminated by ','
  enclosed by '"'
  lines terminated by '\n'
  ;

