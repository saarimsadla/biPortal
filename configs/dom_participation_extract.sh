#!/bin/bash
# Script calls queries for dom participation extracts

sFileNameTXT="dom_participation.txt"
echo "Output file: $sFileNameTXT"

#echo "Removing previously created output file $sFileNameCSV..."
#rm $sFileNameCSV
echo "Removing previously created output file $sFileNameTXT..."
rm $sFileNameTXT

echo "Querying..."

# hrr2.report_type

mysql -u ytb -p944ba223a5 -h dominionprod.ch6nseb8g2s1.us-west-2.rds.amazonaws.com ytbremsc_prod -e "
select vpu.custid,
ygu.loginid,
ygu.account_id,
ygu.premise_id,
from_unixtime(hrr.max_end_date) as last_report_date,
from_unixtime(hrr.min_end_date) as first_report_date,
hrr.maxid as last_report_run_id,
hrr.minid as first_report_run_id,
from_unixtime(hrr.last_runtime),
from_unixtime(hrr.first_runtime),
hrr.report_count as report_count,
hrr2.report_type as last_report_type,
  hrr2.fuel_type,
  (select distinct reportType from tmp.tmp_dom_customer_reports t where t.custId = vpu.custid and t.reportId = hrr.maxid)  as delivery_type,
  vpu.plan as rate_plan,
  lbu.unit_type,
  l.first_name,
  l.last_name,
  replace(ygu.email,'+PEI','') as email,
  ygu.address,
  ygu.address2,
  ygu.city,
  ygu.state,
  ygu.zip,
  ygu.mailing_address,
  ygu.mailing_address2,
  ygu.mailing_city,
  ygu.mailing_state,
  ygu.mailing_zip,
  if(hrcn.custid <> '', 'control','treatment')as treatment_control,
  from_unixtime(efc.date_excluded) as date_of_opt_out,
  efc.reason as opt_out_reason,
  from_unixtime(vpu.date_closed_E),
  hcd.id as cohort_id,
  hcd.cohort_name
    from ytb_gui_client ygu
  left join (select hrcn.custid,hrcn.cohort_id from hurs_runned_controls hrcn where hrcn.cohort_id in (1,6,106)) hrcn on ygu.custid = hrcn.custid
  left join (select hrct.custid,count(*) as report_count, hrr.fuel_type, hrr.report_type, hrr.id, hrr.orig_runned_id, max(hrr.id) as maxid,
  min(hrr.id) as minid, max(hrr.report_end_date) as max_end_date, min(hrr.report_end_date) as min_end_date, max(runtime) as last_runtime,
  min(runtime) as first_runtime, hrct.hurs_runned_report_id
  from hurs_runned_reports hrr
  left join hurs_runned_cohorts hrct on hrct.hurs_runned_report_id = hrr.id
  left  join (select custid,report_id from hurs_bad_delivery_addresses) hbda on hbda.custid = hrct.custid and hbda.report_id = hrct.hurs_runned_report_id
  left join exclude_from_cohort efc1 on efc1.custid = hrct.custid
  where hrr.sent=1 and hrct.cohort_id in (1,6,106)
  and (efc1.date_excluded > hrr.runtime or efc1.date_excluded is null)
  and (hbda.custid is null and hbda.report_id is null)
  group by hrct.custid order by hrr.id desc) hrr on hrr.custid = ygu.custid
  left join hurs_runned_reports hrr2 on hrr2.id = hrr.maxid
  inner join (select if(sum(CASE vpu.status WHEN 'CLOSED' THEN 1 ELSE 0 END) = count(vpu.custid), max(vpu.end_date), null) as date_closed_E
  ,vpu.custid, vpu.plan from vendors_plans_units vpu
  where vpu.meter_id_owh <> '' group by vpu.custid) vpu on vpu.custid = ygu.custid
  left join exclude_from_cohort efc on efc.custid = hrr.custid
  inner join logins_buildings_units lbu on lbu.custid = ygu.custid
  inner join login l on l.loginid = lbu.loginid
  left join hurs_cohort_details hcd on hcd.id = hrr.orig_runned_id or hcd.id = hrcn.cohort_id
  where (hrcn.custid <> '' or hrr.custid <> '')
  group by ygu.custid;
">> $sFileNameTXT

eval "php csv_decrypt_new.php dom_participation.txt dom_participation_d.txt 2,3,16,17,19,20,24,25 $'\t'"

# cat dom_participation_d.txt | tr "\\t" "," > dom_participation_d.csv
# rm -r dom_participation_d.txt
