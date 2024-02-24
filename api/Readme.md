Here we put all the info about Api.
*****------TABLES------****-------------
1]institute 
  fields:- institue_id, location, (address)
2]location
  fields:- location_id, city, 
3]staff
 fields= first_name, last_name, email, gender, mobile_number, age,expreienc, address, subject,  batch_type, batch_slot, staff_location, batch_code 
4]Subject
  Fields:- subject_id, subject_name,  subject_total_hrs
5]Batch
  Fields:- batch_id, batch_code , batch_location, batch_duration, batch_slot, batch_date(starting date), batch_type(nurture / enthuse), 
  <!-- batch_stream(jee/medical) NEED TO ASK SIR?     -->
  
 <!--  ''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''' -->
  (total_number_of_subject, total_number_of_staff,  NEED TO ASK SIR ?)
<!--   --------------------------------------------------------------------------------------------------------------------------------- -->

*******------Master Table-----***********---------------------------------------------------
1) location=  id, city , state
2) subject=   id, subject_name, subject_total_hrs(NEED TO ASK SIR?), 
3) BatchSlot= id, slot_time, 
4) BatchCode= id,  batch_code,
5) durtion=   id, durtion_name,
6) batchtype= id, batch_type()
7) 
