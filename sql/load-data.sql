use sig;

grant all on sig.* to 'sig'@'localhost' identified by 'niR7zK96';
  
truncate instrument;
truncate version;
truncate tab;
truncate question;

insert into instrument values
  (1, 'CSI SIG v2.0');
  
insert into version values
  (1, 1, 't', '2007-10-27');
  
insert into tab values
  (1, 1, 1, null, null, 'Welcome', 'Welcome to the CSI SIG', '<h1>BITS and the Santa Fe Group</h1><h2>Financial Institution Shared Assessments Program Standardized Information Gathering (SIG) Questionnaire<br /></h2>Version 2.0<br /><br />Released: July 25, 2006<br/><br/><a href="http://www.bitsinfo.org/fisap">http://www.bitsinfo.org/fisap</a><br /><a href="mailto:sharedassessments@santa-fe-group.com">sharedassessments@santa-fe-group.com</a>', null),
  (1, 1, 2, null, null, 'Overview', 'An overview of the SIG and it''s purpose', '<h1>Supplemental Information Gathering Overview</h1><p>When applications, systems and services are outsourced, responsibility for reputation, transaction, regulatory and other risks associated with the outsourcing relationship remains with the financial institution. To develop an appropriate risk-mitigation strategy, the institution must be able to identify and understand the controls the service provider relies upon to address risks associated with outsourced services.<p>The Financial Institution Shared Assessments Program was created to develop a standardized approach to obtaining consistent information about a service provider''s information technology practices, processes and controls. The Program consists of two complementary documents: a questionnaire, commonly referred to as the Standardized Information Gathering (SIG) and a set of executable tests, called the Agreed Upon Procedures (AUP). Consistent with ISO 17799:2005*, both documents identify control areas designed to document the service provider''s ability to actively manage information security controls.<p> Standardized Information Gathering (SIG): Developed by Financial Institution Shared Assessments Program members to leverage the BITS IT Service Providers Expectations Matrix and address the control areas covered in ISO 17799:2005, the SIG is a questionnaire to obtain required documentation and establish a profile on operations and controls for each of the control areas. When used as a standalone document, the questionnaire provides information the financial institution needs to evaluate the security controls the service provider''s has in place.<p>Agreed Upon Procedures (AUP): Developed by Financial Institution Shared Assessments Program working group with the Big 4 accounting firms acting as Technical Advisors, the AUPs gather and report on control areas using objective and consistent. Procedures address control objectives in the following areas: Security Policy, Organization of Information Security, Asset Management, Human Resource Security, Physical and Environmental Security, Communications and Operations Management, Access Control, Information Systems Acquisition, Information Security Incident Management, Business Continuity Management, and Compliance. Procedures attest to the existence of controls without rendering opinions of sufficiency thus enabling multiple financial institutions to view results in the context of their own risk tolerance and in the context of industry risk management and regulatory requirements.<p>When the SIG and AUP are combined, financial institutions, service providers, and assessment organizations will have an outline for an evaluation program to obtain information and objectively verify selected controls. As a result, financial institutions will be better able to identify risks, comply with regulatory requirements, and reduce inconsistencies in the evaluation of information received from service providers.<h3>Using the SIG</h3><p>The Standardized Information Gathering (SIG) questionnaire is used to obtain required documentation from a service provider and establish a profile on its operations and controls for each control area. The SIG is based on the BITS IT Service Providers Expectations Matrix, ISO 17799:2005, and risk requirements of member institutions.<h3>Update Process</h3><p>Technology, threats and regulations change. The Financial Institution Shared Assessments Program includes an update process to ensure that the SIG and AUP documents continue to meet risk management and regulatory requirements. The documents are updated at least annually. For more information on the latest releases, please visit the Financial Institution Shared Assessments Program website at <a href="http://www.bitsinfo.org/FISAP">http://www.bitsinfo.org/FISAP</a>', null),
  (1, 1, 3, null, null, 'Business Info', 'General information about your business', null, null);
  
insert into section values
  (1, 1, 3, 1, 1, 'General Information', null, null, null, null, null, null, null),
  (1, 1, 3, 2, 2, 'Specific Information', null, null, null, null, null, null, null);
  
insert into question values
  (1, 1, 1, 1, 1, '1.1.1', 1, 'text', null, null, null, null, null, null, 'Responder Name'),
  (1, 1, 1, 2, 2, '1.1.2', 2, 'text', null, null, null, null, null, null, 'Responder Job Title'),
  (1, 1, 2, 3, 3, '1.1.3', 3, 'text', null, null, null, null, null, null, 'Responder Contact Information'),
  (1, 1, 2, 4, 4, '1.1.4', 4, 'text', null, null, null, null, null, null, 'Date of Response'),
  (1, 1, 2, 5, 5, '1.1.5', 5, 'text', null, null, null, null, null, null, 'List the names and titles of any contributors that assisted');