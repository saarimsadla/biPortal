import sys
import os
import csv
import pymongo
import pymysql
from pymongo import UpdateOne
import subprocess
import json
import random
from bson import ObjectId
from datetime import datetime, timezone
import calendar
import re
import io

'''
{
        "host": "dominionprod.ch6nseb8g2s1.us-west-2.rds.amazonaws.com",
        "user": "hurs_extracts",
        "password": "GpDVmdWamE3qECGJyqrj",
        "database": "ytbremsc_prod",
        "wlId" : [54]
    },
    # "entergyprod.ch6nseb8g2s1.us-west-2.rds.amazonaws.com",
    # "xcelprod.ch6nseb8g2s1.us-west-2.rds.amazonaws.com",
    # "prodvpc-new.ch6nseb8g2s1.us-west-2.rds.amazonaws.com",
'''
mysql_databases = [
    {
        "host": "xcelprod-ar.cluster-ro-ch6nseb8g2s1.us-west-2.rds.amazonaws.com",
        "user": "hurs_extracts",
        "password": "GpDVmdWamE3qECGJyqrj",
        "database": "ytbremsc_prod",
        "wlId" : [46]
    },
    {
        "host": "entergyprod-ar.cluster-ro-ch6nseb8g2s1.us-west-2.rds.amazonaws.com",
        "user": "hurs_extracts",
        "password": "GpDVmdWamE3qECGJyqrj",
        "database": "ytbremsc_prod",
        "wlId" : [38]
    },
    {
        "host": "multiprod-ar.cluster-ro-ch6nseb8g2s1.us-west-2.rds.amazonaws.com",
        "user": "hurs_extracts",
        "password": "GpDVmdWamE3qECGJyqrj",
        "database": "ytbremsc_prod",
        "wlId" : [47,52]
    }
]

def get_mysql_connection(wlId):
    for db in mysql_databases:
        if wlId in db['wlId']:  # Checks if wlId exists in the list
            return pymysql.connect(
                host=db['host'],
                user=db['user'],
                password=db['password'],
                db=db['database'],
                local_infile=1,  # Enable loading local files
                cursorclass=pymysql.cursors.DictCursor
            )
    return None

def getClientByWlId(wlid_prm):
    # MongoDB connection
    #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
    mongoDb = "mongodb://hersadmin:P0rt0f!n0@mongoa.pecosys.com:27017,mongob.pecosys.com:27017,mongoc.pecosys.com:27017/?authSource=admin&readPreference=secondaryPreferred&replicaSet=rs1"
    client = pymongo.MongoClient(mongoDb)
    db_hursPortal = client['hursPortal'] # QA_Samples
    ClientsCol = db_hursPortal['client_details']
    clnt_query = {
            "wlId": int(wlid_prm)
        }
    clnt_projection = {
        "clientAbr": 1,
        "_id": 0
    }

    clnt_result = ClientsCol.find(clnt_query, clnt_projection)

    client_abr = clnt_result[0]['clientAbr']

    return client_abr

def getRuntimeVals(repId, wlId):
    qry = f"select DATE_FORMAT(FROM_UNIXTIME(runtime), '%Y%m%d') runtime from hurs_runned_reports where id = {repId} limit 1;"
    python_command = ["python3.9", "/var/www/html/hursPortal/pythonScripts/mysql_query.py", str(wlId), qry]
    
    try:
        ini_out = subprocess.check_output(python_command, stderr=subprocess.STDOUT)
        sndDtls = json.loads(ini_out)
        
        res = 0
        for r in sndDtls:
            res = r['runtime']
        
        return res
    except subprocess.CalledProcessError as e:
        print(f"Command failed with error: {e.output.decode()}")
        return None

def getRunType(date):
    # Convert the date string to a DateTime object
    dateObj = datetime.strptime(date, '%Y%m%d')
    
    # Get the day of the month
    day = int(dateObj.strftime('%d'))
    
    # Get the total number of days in the month
    daysInMonth = calendar.monthrange(dateObj.year, dateObj.month)[1]
    
    # Determine if the date is closer to the start or end of the month
    if day <= 5 or day >= daysInMonth - 9:
        return "Billing Run"
    else:
        return "AMI Run"
    

# -- ------------------------------- here is the code for dcs data comparisons ------------------------------- --

def clean_field(value, fallback):
    if value is None or str(value).strip() == "":
        return str(fallback).strip()
    return str(value).strip()


def is_valid_ascii(text):
    try:
        text.encode('utf-8').decode('ascii')
        return True
    except UnicodeDecodeError:
        return False


def getCustDataMysql(Custid, WLId):
    #select custid,account_id, name, address, address2 from ytb_gui_client where custid  =14161414;
    conn = get_mysql_connection(WLId)
    if conn is None:
        print(f"No MySQL connection found for wlId: {WLId}")
        return
    mysqlQuery = f"""
                    select y.custid, y.account_id, l.first_name, l.last_name, y.address, y.address2, y.mailing_address, y.mailing_address2
                    from ytb_gui_client y
                    left join login l on y.loginid = l.loginid
                    where custid  = {str(Custid)};
                """
    #print(mysqlQuery)

    try:
        with conn.cursor() as cursor:
            cursor.execute(mysqlQuery)
            result = list(cursor.fetchall())
            if result: 
                data = result
                #print(data)
                
                # File name
                columns = "1,2,3,4,5,6,7"
                filename = f"/var/www/html/hursPortal/phpRunners/customer_data_{Custid}.csv"
                decfilename = f"/var/www/html/hursPortal/phpRunners/Dec_customer_data_{Custid}.csv"

                # Writing to CSV
                with open(filename, mode='w', newline='') as file:
                    writer = csv.DictWriter(file, fieldnames=data[0].keys())
                    writer.writeheader()
                    writer.writerows(data)

                commandDec = ["php", "-f", "csv_decrypt_new.php", filename, decfilename, columns] #/var/www/html/hursPortal/phpRunners/
                #print(commandDec)
                result = subprocess.run(commandDec, capture_output=True, text=True)
                #print(result)

                if (result.stderr or "EECL" in str(result.stdout)) and ("PHP WARNING" not in str(result.stdout).upper() and "PHP WARNING" not in str(result.stderr).upper()):
                    print(f"Error Decrypting customer data: {result.stderr}")
                    sys.exit(1)
                else:
                    with open(decfilename, mode='r', newline='') as file:
                        reader = csv.reader(file)
                        next(reader) # Skip header
                        first_row = next(reader, None)
                        # Go into `if` only if there's at least one non-whitespace string
                        if first_row and any(re.search(r'\S', item) for item in first_row):

                            #print(first_row)
                            os.remove(filename)
                            os.remove(decfilename)
                            nme = str(first_row[2]).strip() + " " + str(first_row[3]).strip()
                            return {
                                "custid": Custid,
                                "account_id": str(first_row[1]).strip(),
                                "name": nme,
                                "address": first_row[4], 
                                "address2": first_row[5] 
                            }
                            #clean_field(first_row[4], first_row[6]),
                            #clean_field(first_row[5], first_row[7])


                            # Adjust the slice if needed
                        else:
                            os.remove(filename)
                            os.remove(decfilename)
                            return {
                                "custid": Custid,
                                "account_id": "Not Found",
                                "name": "Not Found",
                                "address": "Not Found", 
                                "address2": "Not Found" 
                            }
            else: 
                print(f"No results fecthed from mysql for customer data")
                sys.exit(1)
    except Exception as e:
        print(f"Error fetching Mysql customer data: {e}")
        sys.exit(1)

'''
# this code runs decryption virtually
def getCustDataMysql(Custid, WLId):
    conn = get_mysql_connection(WLId)
    if conn is None:
        print(f"No MySQL connection found for wlId: {WLId}")
        return

    mysqlQuery = f"""
        SELECT y.custid, y.account_id, l.first_name, l.last_name,
               y.address, y.address2, y.mailing_address, y.mailing_address2
        FROM ytb_gui_client y
        LEFT JOIN login l ON y.loginid = l.loginid
        WHERE custid = {str(Custid)};
    """

    try:
        with conn.cursor() as cursor:
            cursor.execute(mysqlQuery)
            result = list(cursor.fetchall())
            if not result:
                print(f"No results fetched from MySQL for customer data")
                sys.exit(1)

            data = result
            columns = "1,2,3,4,5,6,7"

            # Write CSV to memory
            output_csv = io.StringIO()
            writer = csv.DictWriter(output_csv, fieldnames=data[0].keys())
            writer.writeheader()
            writer.writerows(data)

            # Get CSV content
            csv_string = output_csv.getvalue()
            output_csv.close()

            # Call PHP and pass CSV as stdin, expect decrypted CSV as stdout
            commandDec = ["php", "csv_decrypt_new_virtual.php", "-", "-", columns]
            process = subprocess.Popen(
                commandDec,
                stdin=subprocess.PIPE,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )

            stdout, stderr = process.communicate(input=csv_string)

            if process.returncode != 0 or stderr or "EECL" in stdout:
                if "PHP WARNING" not in stderr.upper() and "PHP WARNING" not in stdout.upper():
                    print(f"Error Decrypting customer data: {stderr}")
                    sys.exit(1)

            # Parse decrypted CSV from stdout
            decrypted_csv = io.StringIO(stdout)
            reader = csv.reader(decrypted_csv)
            next(reader, None)  # Skip header
            first_row = next(reader, None)
            decrypted_csv.close()

            if first_row and any(re.search(r'\S', item) for item in first_row):
                nme = str(first_row[2]).strip() + " " + str(first_row[3]).strip()
                return {
                    "custid": Custid,
                    "account_id": str(first_row[1]).strip(),
                    "name": nme,
                    "address": first_row[4],
                    "address2": first_row[5]
                }
            else:
                return {
                    "custid": Custid,
                    "account_id": "Not Found",
                    "name": "Not Found",
                    "address": "Not Found",
                    "address2": "Not Found"
                }

    except Exception as e:
        print(f"Error fetching Mysql customer data: {e}")
        sys.exit(1)
'''
def get_current_and_last_year():
    current_year = datetime.now().year
    last_year = current_year - 1
    return current_year, last_year

def get_sql_filter_for_last_n_months(lmt, month, year):
    from collections import defaultdict

    # Dictionary to hold months grouped by year
    lmt = int(lmt)
    month = int(month)
    year = int(year)
    '''print(lmt)
    print(month)
    print(year)'''
    year_month_map = defaultdict(list)

    # Generate the last `lmt` months
    for _ in range(lmt):
        year_month_map[year].append(month)
        month -= 1
        if month == 0:
            month = 12
            year -= 1

    # Build SQL condition string
    conditions = []
    for y in sorted(year_month_map.keys(), reverse=True):
        months_str = ",".join(str(m) for m in sorted(year_month_map[y], reverse=True))
        conditions.append(f"(year(from_Unixtime(br.billing_date)) in ({y}) and month(from_Unixtime(br.billing_date)) in ({months_str}))")

    return "and (" + " or ".join(conditions) + ")"



def getBillingDataMysql(Custid, WLId, yer, mnth, tble):
    #select custid,account_id, name, address, address2 from ytb_gui_client where custid  =14161414;
    conn = get_mysql_connection(WLId)
    current, last = get_current_and_last_year()
    sql_filter = get_sql_filter_for_last_n_months(6, mnth, yer)

    mt = mnth - 1
    yr = yer

    kwVal = 0
    thermVal = 0
    ubVals = {}

    if conn is None:
        print(f"No MySQL connection found for wlId: {WLId}")
        return
    mysqlQuery = f"""
                    select custid, year, period, sum(if(ifnull(kw_usage,0)=0,kw_usage_base,kw_usage)) kwUsage
                    , sum(if(ifnull(therm_usage,0)=0,therm_usage_base,therm_usage)) tu  
                    from {tble} where custid = {str(Custid)} and year in ({current},{last})
                    group by custid, year, period
                    order by year desc, period desc;
                """
    #print(mysqlQuery)
    try:
        with conn.cursor() as cursor:
            cursor.execute(mysqlQuery)
            result = list(cursor.fetchall())
            if result: 
                data = result
                #print (data)
                for dt in data:
                    if len(ubVals) <= 6:
                        keyVal = str(dt['year']) + "-" + str(dt['period'])
                        ubVals[keyVal] = {
                            "kwVal":f"{float(dt['kwUsage']):.2f}",
                            "thVal":f"{float(dt['tu']):.2f}"
                        }
                    else:
                        break

                    '''print (yr)
                    print("Y" if dt['year'] == yr else "N")
                    print(mt)
                    print("Y" if dt['period'] == mt else "N")
                    print(int(dt['tu']))
                    print("Y" if int(dt['tu'])> 0 else "N")
                    print(thermVal)
                    print("Y" if thermVal <= 0 else "N")'''
                    if dt['year'] == yer and dt['period'] == mnth and kwVal <= 0:
                        kwVl = float(dt['kwUsage'])
                        kwVal = f"{kwVl:.2f}"
                    if dt['year'] == yr and dt['period'] == mt and int(dt['tu']) > 0 and thermVal <= 0:
                        thrmVl = float(dt['tu'])
                        thermVal = f"{thrmVl:.2f}"
                    else:
                        if dt['period'] == mt and int(dt['tu']) <= 0 and thermVal <= 0:
                            mt = mt - 1
                            if mt == 0:
                                mt = 12
                                yr = last

    except Exception as e:
        print(f"Error fetching Mysql {tble} Billing data: {e}")
        sys.exit(1)
    #json.dumps(
    return kwVal,thermVal, ubVals


def getRewardsDataMysql(Custid, WLId):
    #select custid,account_id, name, address, address2 from ytb_gui_client where custid  =14161414;
    conn = get_mysql_connection(WLId)
    current, last = get_current_and_last_year()
    
    pointsEarned = 0
    pointsRedeemed = 0
    pointsExpired = 0
    pointsAvilable = 0

    if conn is None:
        print(f"No MySQL connection found for wlId: {WLId}")
        return
    mysqlQuery = f"""
                    select 
						ytb.custid, pt.loginid , sum(pt.points) pointEarned
						,( 
							SELECT IFNULL( SUM( rt.points ), 0 ) 
							FROM points_redeemed_or_transferred AS rt 
							WHERE rt.loginid = pt.loginid
						) pointsRedeemed 
						,( 
							SELECT IFNULL( SUM( e.points ), 0 ) 
							FROM points_expired AS e 
							WHERE e.loginid = pt.loginid AND expired = 1
						) pointsExpired
						,sum( pt.points ) - (
													( 
														SELECT IFNULL( SUM( rt.points ), 0 ) 
														FROM points_redeemed_or_transferred AS rt 
														WHERE rt.loginid = pt.loginid
													) 
														+ 
													( 
														SELECT IFNULL( SUM( e.points ), 0 ) 
														FROM points_expired AS e 
														WHERE e.loginid = pt.loginid AND expired = 1
													) 
											) AS pointsAvailable
						from points pt
						inner join ytb_gui_client ytb
						on ytb.loginid = pt.loginid
                        where ytb.custid = {str(Custid)}
						group by ytb.custid, pt.loginid limit 0,1;
                """
    #print(mysqlQuery)
    try:
        with conn.cursor() as cursor:
            cursor.execute(mysqlQuery)
            result = list(cursor.fetchall())
            if result: 
                data = result
                #print (data)
                for dt in data:
                    pointsEarnd = float(dt['pointEarned'])
                    pointsEarned = f"{pointsEarnd:.2f}"
                    pointsRedeemd =float(dt['pointsRedeemed'])
                    pointsRedeemed = f"{pointsRedeemd:.2f}"
                    pointsExpird = float(dt['pointsExpired'])
                    pointsExpired = f"{pointsExpird:.2f}"
                    pointsAvilabl = float(dt['pointsAvailable'])
                    pointsAvilable = f"{pointsAvilabl:.2f}"
                    

    except Exception as e:
        print(f"Error fetching Mysql Points data: {e}")
        sys.exit(1)
    
    return pointsEarned, pointsRedeemed, pointsExpired, pointsAvilable


def month_name_to_number(month_name):
    try:
        # Capitalize the first letter to match the expected format
        formatted_name = month_name.capitalize()
        month_number = datetime.strptime(formatted_name, "%B").month
        return month_number
    except ValueError:
        return "Invalid month name"


def getDcsVsData(dcsCol,Custid, ReportId, WLId):
   
    dcsQr = {
        "$or": [
            {"wlId": int(WLId)},
            {"whitelabel": int(WLId)}
        ],
        "reportId": ReportId,
        "custId": Custid
    }

    dta = dcsCol.find(dcsQr)

    custData = getCustDataMysql(Custid, WLId)

    #print(custData)

    acceptedRangeDenomElc = 0.99
    acceptedRangeDenomGas = 0.99

    dcsObj = None

    #print(dta)
    dtaa= list(dta)
    #print(dtaa)
    if dtaa:
        for dt in dtaa:
            #print(dt)
            user = dt.get('data', {}).get('user', {})
            comparables = dt.get('data', {}).get('comparables', [])
            rewards = dt.get('data', {}).get('rewards', {})

            ft0 = comparables[0].get('fuelType', "") if len(comparables) > 0 else ""

            ft1 = comparables[1].get('fuelType', "") if len(comparables) > 1 else ""

            if ft0 != "" or ft1 != "":
                kwV, thrmV, ubVals = getBillingDataMysql(Custid, WLId, dt.get('reportYear', 0), month_name_to_number(dt.get('reportMonth', 0).lower()),"utilbill_archive")
                kwVR, thrmVR, rbVals = getBillingDataMysql(Custid, WLId, dt.get('reportYear', 0), month_name_to_number(dt.get('reportMonth', 0).lower()),"bill_history_raw")
            
            pointsEarned, pointsRedeemed, pointsExpired, pointsAvilable = getRewardsDataMysql(Custid, WLId)

            dcsObj = {
                "reportYear": dt.get('reportYear', 0),
                "reportMonth": dt.get('reportMonth', 0),
                "reportMonthNum": month_name_to_number(dt.get('reportMonth', 0).lower()),
                "account": user.get('account', ""),
                "account_p": custData.get('account_id',""),
                "account_check": "",
                "address": (user.get('address', "") + ' ' + user.get('address2', "")).strip(),
                "address_p": (custData.get('address',"") + ' ' + custData.get('address2',"")).strip(),
                "address_check": "",
                "name": (user.get('firstName', "") + ' ' + user.get('lastName', "")).strip(),
                "name_p": (custData.get('name',"")).strip(),
                "name_check": "",
                "fuelType0": ft0,
                "user0": comparables[0].get('usage', [{}])[0].get('user', 0) if len(comparables) > 0 and len(comparables[0].get('usage', [])) > 0 else 0,
                "user0_P": kwV,
                "user0_PR": kwVR,
                "user0_check": "",
                "similar0": comparables[0].get('usage', [{}])[1].get('similar', 0) if len(comparables) > 0 and len(comparables[0].get('usage', [])) > 1 else 0,
                "efficient0": comparables[0].get('usage', [{}])[2].get('efficient', 0) if len(comparables) > 0 and len(comparables[0].get('usage', [])) > 2 else 0,
                "fuelType1": ft1,
                "user1": comparables[1].get('usage', [{}])[0].get('user', 0) if len(comparables) > 1 and len(comparables[1].get('usage', [])) > 0 else 0,
                "user1_P": thrmV,
                "user1_PR": thrmVR,
                "user1_check": "",
                "ub_history": ubVals,
                "rb_history": rbVals,
                "similar1": comparables[1].get('usage', [{}])[1].get('similar', 0) if len(comparables) > 1 and len(comparables[1].get('usage', [])) > 1 else 0,
                "efficient1": comparables[1].get('usage', [{}])[2].get('efficient', 0) if len(comparables) > 1 and len(comparables[1].get('usage', [])) > 2 else 0,
                "rewardsPoints": rewards.get('points', 0),
                "rewardsPointsAvilable": pointsAvilable,
                "rewardsPoints_check": "",
                "rewardsPointsEarned": pointsEarned,
                "rewardsPointsRedeemed": pointsRedeemed,
                "rewardsPointsExpired": pointsExpired
            }

        #print(dcsObj)

        if dcsObj.get('account',"").strip() in [None, ""] or dcsObj.get('account_p',"").strip() in [None, ""]:
            dcsObj['account_check'] = "NF"
        else:
            dcsObj['account_check'] = "Y" if dcsObj['account'].strip() == dcsObj['account_p'].strip() else "N"


        if dcsObj.get('address') in [None, ""] or dcsObj.get('address_p') in [None, ""]:
            dcsObj['address_check'] = "NF"
        else:
            dcsObj['address_check'] = "Y" if dcsObj['address'] == dcsObj['address_p'] else "N"


        if dcsObj.get('name') in [None, ""] or dcsObj.get('name_p') in [None, ""]:
            dcsObj['name_check'] = "NF"
        else:
            dcsObj['name_check'] = "Y" if dcsObj['name'] == dcsObj['name_p'] else "N"


        if dcsObj.get('user0') in [None, "",0] or dcsObj.get('user0_P') in [None, "",0]:
            if int(float(dcsObj.get('user0'))) > 0 and int(float(dcsObj.get('user0_P'))) <= 0 or int(float(dcsObj.get('user0'))) <= 0 and int(float(dcsObj.get('user0_P'))) > 0:
                dcsObj['user0_check'] = "N"
            else:
                dcsObj['user0_check'] = "NF"
        else:
            dcsObj['user0_check'] = "Y" if ((float(dcsObj['user0_P']) - acceptedRangeDenomElc) <= int(float(dcsObj['user0'])) <= (float(dcsObj['user0_P']) + acceptedRangeDenomElc)) else "N"
        

        if dcsObj.get('user1') in [None, "",0] or dcsObj.get('user1_P') in [None, "",0]:
            if int(float(dcsObj.get('user1'))) > 0 and int(float(dcsObj.get('user1_P'))) <= 0 and int(float(dcsObj.get('user1'))) <= 0 and int(float(dcsObj.get('user1_P'))) > 0:
                dcsObj['user1_check'] = "N"
            else:
                dcsObj['user1_check'] = "NF"
        else:
            dcsObj['user1_check'] = "Y" if ((float(dcsObj['user1_P'])) - acceptedRangeDenomGas <= int(float(dcsObj['user1'])) <= (float(dcsObj['user1_P']) + acceptedRangeDenomGas)) else "N"

        
        if dcsObj.get('rewardsPoints') in [None, "",0] or dcsObj.get('rewardsPointsAvilable') in [None, "",0]:
            if int(float(dcsObj.get('rewardsPoints'))) > 0 and int(float(dcsObj.get('rewardsPointsAvilable'))) <= 0 or int(float(dcsObj.get('rewardsPoints'))) <= 0 and int(float(dcsObj.get('rewardsPointsAvilable'))) > 0:
                dcsObj['rewardsPoints_check'] = "N"
            else:
                dcsObj['rewardsPoints_check'] = "NF"
        else:
            dcsObj['rewardsPoints_check'] = "Y" if int(float(dcsObj['rewardsPoints'])) == int(float(dcsObj['rewardsPointsAvilable'])) else "N"

        if (
        dcsObj['account_check'] == "Y"
        and dcsObj['address_check'] == "Y"
        and dcsObj['name_check'] == "Y"
        and (ft0 in [None, ""] or dcsObj['user0_check'] == "Y")
        and (ft1 in [None, ""] or dcsObj['user1_check'] == "Y")
        and dcsObj['rewardsPoints_check'] == "Y"
        ):
            dcsObj['overallRes'] = "Y"
        else:
            dcsObj['overallRes'] = "N"



    return dcsObj

# -- ------------------------------- here is the code for dcs data comparisons ENDS ------------------------------- --



def main():
    if len(sys.argv) != 7:
        print("Usage: python3.9 generateSamples2.py <frmRepId> <toRepId> <sndT> <wlId> <usrNme> <reason>")
        return

    frRpId = int(sys.argv[1])
    toRpId = int(sys.argv[2])
    sndTyp = sys.argv[3]
    wlIds = int(sys.argv[4])
    usr = sys.argv[5]
    rs = sys.argv[6]
    tpVal = 0
    im_samples_query = None


    # MongoDB connection
    mongoDb = "mongodb://hersadmin:P0rt0f!n0@mongoa.pecosys.com:27017,mongob.pecosys.com:27017,mongoc.pecosys.com:27017/?authSource=admin&readPreference=secondaryPreferred&replicaSet=rs1"
    client = pymongo.MongoClient(mongoDb)
    db_hers = client['hers'] # imaging
    db_hursPortal = client['hursPortal'] # QA_Samples

    imagingCol = db_hers['imaging']
    QASampleCol = db_hursPortal['QASamples']
    dcsCol  = db_hers['data_service']

    
    message = ""

    if frRpId > toRpId:
        frRpId, toRpId = toRpId, frRpId

    if frRpId > 0 and toRpId <= 0:
        toRpId = frRpId

    if frRpId <= 0 and toRpId > 0:
        frRpId = toRpId

    if frRpId > 0 or toRpId > 0:
        im_samples_query = {
            "wlId": int(wlIds)
            ,"reportId": {"$gte": frRpId, "$lte": toRpId}
        }
    else:
        im_samples_query = {
            "wlId": int(wlIds)
        }
    # get unique reportId(S) in imaging
    
    im_samples_projection = {
        "reportId": 1,
        "_id": 0
    }
    im_samples_result = imagingCol.find(im_samples_query, im_samples_projection)
    unique_rep_ids = {doc["reportId"] for doc in im_samples_result}
    #print("Unique img ids:", list(unique_rep_ids))
    '''unique_rep_ids = set()
    for doc in im_samples_result:
        unique_rep_ids.add(doc["reportId"])
        if len(unique_rep_ids) == 10:
            break'''


    # Query QA_Samples to get unique custIds and reportIds
    # Define the query and projection
    qa_samples_query = {
        "wlId": int(wlIds),
        "qaRemoved" : "N"
    }
    qa_samples_projection = {
       # "custId": 1,
        "reportId": 1,
        "_id": 0
    }

    # Execute the query
    qa_samples_result = QASampleCol.find(qa_samples_query, qa_samples_projection)

    # Convert the cursor to a list to print and iterate multiple times
    qa_samples_list = list(qa_samples_result)

    # Print the raw query result
    #print("Query Result:", qa_samples_list)

    # Extract unique custIds and reportIds
   # unique_cust_ids = {doc["custId"] for doc in qa_samples_list}
    unique_rpt_ids = {doc["reportId"] for doc in qa_samples_list}
    #print("Unique reportIds:", list(unique_rpt_ids))

    message += "{"
    ctr = 1
    for repId in unique_rep_ids:
        #print(repId)

        wlID = wlIds

        # Print the unique custIds and reportIds
        #print("Unique custIds:", list(unique_cust_ids))
        
        # "reportId": { "$nin": list(unique_rpt_ids) }
        if repId in unique_rpt_ids:
            message += '"' + str(repId) + '" : {'
            message += '"Status": "Sample Already generated"'
            message += f', "Email_samples_size": 0'
            message += f', "Paper_samples_size": 0'
            message += f', "GenStats":"N/A"'
            message += "}"

            if len(unique_rep_ids) > 1 and ctr < len(unique_rep_ids):
                message += ","
                ctr += 1
            
            continue
        else:
            message += '"' + str(repId) + '" : {'
        
        # Parse custIds from rs
        cust_ids = None
        custidDocsE = None
        custidDocsP = None
        if rs is not None or rs.strip() != "":
            cust_ids = [int(cid.strip()) for cid in rs.split(",") if cid.strip()]

        
        if cust_ids:
            matchStageE = [
                {
                    "$match": {
                    "reportId": repId,
                    "reportType": "email",
                    "wlId": wlID,
                    "exclusions":{"$exists": False},
                    "custId":{"$in":cust_ids}
                    }
                },
                {
                    "$addFields": {
                    "reportKey": {
                        "$concat": [
                        { "$toString": "$reportId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$project": {
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "custId": 1,
                    "printReport": 1,
                    "imaging_id": "$_id",
                    "reportKey": 1,
                    "_id": 0
                    }
                },
                {
                    "$lookup": {
                    "from": "report_runs",
                    "let": { "reportKey": "$reportKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "reportKey": {
                            "$concat": [
                                { "$toString": "$reportId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$reportKey", "$$reportKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "reportId": 1,
                            "wlId": 1
                        }
                        }
                    ],
                    "as": "reportRuns"
                    }
                },
                { "$unwind": "$reportRuns" },
                {
                    "$addFields": {
                    "groupKey": {
                        "$concat": [
                        { "$toString": "$reportRuns.cohortId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$lookup": {
                    "from": "cohorts",
                    "let": { "groupKey": "$groupKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "groupKey": {
                            "$concat": [
                                { "$toString": "$cohortId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$groupKey", "$$groupKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "cohort_name": "$name",
                            "groupName": 1
                        }
                        }
                    ],
                    "as": "groupDetails"
                    }
                },
                { "$unwind": "$groupDetails" },
                {
                    "$project": {
                    "imaging_id": 1,
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "cohortId": "$reportRuns.cohortId",
                    "custId": 1,
                    "printReport": 1,
                    "groupName": "$groupDetails.groupName",
                    "cohort_name": "$groupDetails.cohort_name"
                    }
                },
                {
                    "$sample": { "size": 100 }
                }
                ]
            
            matchStageP = [
                {
                    "$match": {
                    "reportId": repId,
                    "reportType": "paper",
                    "wlId": wlID,
                    "exclusions":{"$exists": False},
                    "custId":{"$in":cust_ids}
                    }
                },
                {
                    "$addFields": {
                    "reportKey": {
                        "$concat": [
                        { "$toString": "$reportId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$project": {
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "custId": 1,
                    "printReport": 1,
                    "imaging_id": "$_id",
                    "reportKey": 1,
                    "_id": 0
                    }
                },
                {
                    "$lookup": {
                    "from": "report_runs",
                    "let": { "reportKey": "$reportKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "reportKey": {
                            "$concat": [
                                { "$toString": "$reportId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$reportKey", "$$reportKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "reportId": 1,
                            "wlId": 1
                        }
                        }
                    ],
                    "as": "reportRuns"
                    }
                },
                { "$unwind": "$reportRuns" },
                {
                    "$addFields": {
                    "groupKey": {
                        "$concat": [
                        { "$toString": "$reportRuns.cohortId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$lookup": {
                    "from": "cohorts",
                    "let": { "groupKey": "$groupKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "groupKey": {
                            "$concat": [
                                { "$toString": "$cohortId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$groupKey", "$$groupKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "cohort_name": "$name",
                            "groupName": 1
                        }
                        }
                    ],
                    "as": "groupDetails"
                    }
                },
                { "$unwind": "$groupDetails" },
                {
                    "$project": {
                    "imaging_id": 1,
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "cohortId": "$reportRuns.cohortId",
                    "custId": 1,
                    "printReport": 1,
                    "groupName": "$groupDetails.groupName",
                    "cohort_name": "$groupDetails.cohort_name"
                    }
                },
                {
                    "$sample": { "size": 100 }
                }
                ]
            custidDocsE = list(imagingCol.aggregate(matchStageE))
            custidDocsP = list(imagingCol.aggregate(matchStageP))
        

        age= [
                {
                    "$match": {
                    "reportId": repId,
                    "reportType": "email",
                    "wlId": wlID,
                    "exclusions":{"$exists": False}
                    }
                },
                {
                    "$addFields": {
                    "reportKey": {
                        "$concat": [
                        { "$toString": "$reportId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$project": {
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "custId": 1,
                    "printReport": 1,
                    "imaging_id": "$_id",
                    "reportKey": 1,
                    "_id": 0
                    }
                },
                {
                    "$lookup": {
                    "from": "report_runs",
                    "let": { "reportKey": "$reportKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "reportKey": {
                            "$concat": [
                                { "$toString": "$reportId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$reportKey", "$$reportKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "reportId": 1,
                            "wlId": 1
                        }
                        }
                    ],
                    "as": "reportRuns"
                    }
                },
                { "$unwind": "$reportRuns" },
                {
                    "$addFields": {
                    "groupKey": {
                        "$concat": [
                        { "$toString": "$reportRuns.cohortId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$lookup": {
                    "from": "cohorts",
                    "let": { "groupKey": "$groupKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "groupKey": {
                            "$concat": [
                                { "$toString": "$cohortId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$groupKey", "$$groupKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "cohort_name": "$name",
                            "groupName": 1
                        }
                        }
                    ],
                    "as": "groupDetails"
                    }
                },
                { "$unwind": "$groupDetails" },
                {
                    "$project": {
                    "imaging_id": 1,
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "cohortId": "$reportRuns.cohortId",
                    "custId": 1,
                    "printReport": 1,
                    "groupName": "$groupDetails.groupName",
                    "cohort_name": "$groupDetails.cohort_name"
                    }
                },
                {
                    "$sample": { "size": 100 }
                }
                ]
                

        agp= [
                {
                    "$match": {
                    "reportId": repId,
                    "reportType": "paper",
                    "wlId": wlID,
                    "exclusions":{"$exists": False}
                    }
                },
                {
                    "$addFields": {
                    "reportKey": {
                        "$concat": [
                        { "$toString": "$reportId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$project": {
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "custId": 1,
                    "printReport": 1,
                    "imaging_id": "$_id",
                    "reportKey": 1,
                    "_id": 0
                    }
                },
                {
                    "$lookup": {
                    "from": "report_runs",
                    "let": { "reportKey": "$reportKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "reportKey": {
                            "$concat": [
                                { "$toString": "$reportId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$reportKey", "$$reportKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "reportId": 1,
                            "wlId": 1
                        }
                        }
                    ],
                    "as": "reportRuns"
                    }
                },
                { "$unwind": "$reportRuns" },
                {
                    "$addFields": {
                    "groupKey": {
                        "$concat": [
                        { "$toString": "$reportRuns.cohortId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$lookup": {
                    "from": "cohorts",
                    "let": { "groupKey": "$groupKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "groupKey": {
                            "$concat": [
                                { "$toString": "$cohortId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$groupKey", "$$groupKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "cohort_name": "$name",
                            "groupName": 1
                        }
                        }
                    ],
                    "as": "groupDetails"
                    }
                },
                { "$unwind": "$groupDetails" },
                {
                    "$project": {
                    "imaging_id": 1,
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "cohortId": "$reportRuns.cohortId",
                    "custId": 1,
                    "printReport": 1,
                    "groupName": "$groupDetails.groupName",
                    "cohort_name": "$groupDetails.cohort_name"
                    }
                },
                {
                    "$sample": { "size": 100 }
                }
                ]

        #
        res_email_image = list(imagingCol.aggregate(age))
        res_paper_image = list(imagingCol.aggregate(agp))

        resImgEmailSze = len(res_email_image) if res_email_image else 0
        resImgPaperSze = len(res_paper_image) if res_paper_image else 0

        resCustDocsESze = len(custidDocsE) if custidDocsE else 0  
        resCustDocsPSze = len(custidDocsP) if custidDocsP else 0  

        # move loop forwards if no data to generate samples
        if (resImgEmailSze + resImgPaperSze) <= 50 and (resCustDocsESze + resCustDocsPSze) <= 50:
            continue

        
        samplesE = custidDocsE[:] if custidDocsE is not None else []
        remainingE = 25 - len(samplesE)

        samplesP = custidDocsP[:] if custidDocsP is not None else []
        remainingP = 25 - len(samplesP)

        final_samples = []
        messageParts = []


        if remainingE > 0:
            if remainingE == 25:
                messageParts.append("No Fixed customers for email found, filled randomly")
            else:
                messageParts.append("Fixed customers for email found, remianing filled randomly")
            
            if resImgEmailSze >= (remainingE + 50):
                filler = [rec for rec in res_email_image if rec not in samplesE]
                samplesE.extend(random.sample(filler, min(remainingE, len(filler))))
            else:
                samplesE = []

        if remainingP > 0:
            if remainingP == 25:
                messageParts.append("No Fixed customers for print found, filled randomly")
            else:
                messageParts.append("Fixed customers for print found, remaining filled randomly")

            if resImgPaperSze >= (remainingP + 50):
                filler = [rec for rec in res_paper_image if rec not in samplesP]
                samplesP.extend(random.sample(filler, min(remainingP, len(filler))))
            else:
                samplesP = []
        
        # Checking if both are under needed limit then skip
        if len(samplesE) < 25 and len(samplesP) < 25:
            messageParts.append("Not enough data for sampling in both email and paper")
            continue

        final_samples = samplesE + samplesP

        eSize = len(samplesE)
        pSize = len(samplesP)
        # Fill remaining slots equally from available sources
        remaining_slots = 50 - len(final_samples)
        if remaining_slots > 0:
            messageParts.append("Data from email or paper missing substituting accordingly")
            half = remaining_slots // 2
            extra = remaining_slots % 2

            fillerE = [rec for rec in res_email_image if rec not in final_samples]
            fillerP = [rec for rec in res_paper_image if rec not in final_samples]

            email_fill = min(half + extra, len(fillerE))
            paper_fill = min(half, len(fillerP))

            # If one source is insufficient, fill more from the other
            if email_fill + paper_fill < remaining_slots:
                if len(fillerE) >= remaining_slots:
                    email_fill = remaining_slots
                    paper_fill = 0
                elif len(fillerP) >= remaining_slots:
                    paper_fill = remaining_slots
                    email_fill = 0
                else:
                    # Fill as much as possible from both
                    email_fill = len(fillerE)
                    paper_fill = remaining_slots - email_fill

            final_samples.extend(random.sample(fillerE, email_fill))
            final_samples.extend(random.sample(fillerP, paper_fill))

            eSize = eSize + email_fill
            pSize = pSize + paper_fill   

        # Trim to exactly 50
        final_samples = final_samples[:50]


        message += '"Status": "' + ' | '.join(messageParts) + '"'

        message += f', "Email_samples_size": {eSize}'
        message += f', "Paper_samples_size": {pSize}'

        # Use these variables as return values or pass forward:
        # final_samples, samplesE, samplesP, message
        #

        # Insert multiple documents
        #QASampleCol.insert_many(final_samples)

        # Create a list of UpdateOne operations for upsert
        operations = []
        srn = 0
        dateTimeVal = datetime.now(timezone.utc)
        for sample in final_samples:
            srn += 1
            sample['sr'] = srn
            sample['client'] = getClientByWlId(sample["wlId"])
            sample['downFileNme'] = sample['client'] + '_' + str(sample['cohort_name']).replace(' ', '_') + '_' + sample['reportType'] + '_' + str(sample['wlId']) + '_' + str(sample['reportId']) + '_' + str(sample['custId']) + '.pdf'
            sample['runtime'] = getRuntimeVals(sample['reportId'],sample["wlId"])
            sample['runtype'] = getRunType(sample['runtime'])
            sample['passedQA'] = "N"
            sample['showToPms'] = "N"
            sample['qaRemoved'] = "N"
            res = getDcsVsData(dcsCol, sample['custId'], sample['reportId'], sample["wlId"])
            #print(json.dumps(res))
            sample['repVsPortal'] = res
            sample['qaReason'] = {
                                    "0": {
                                        "commenterType": "SYS",
                                        "madeBy": usr,
                                        "madeOn": dateTimeVal,
                                        "comment": ""
                                    }
                                }
            sample['wooFields'] = {
                                    "createdOn": dateTimeVal,
                                    "updatedOn": dateTimeVal,
                                    "createdBy": usr,
                                    "updatedBy": usr
                                }


            operation = UpdateOne(
                {"custId": sample["custId"], "reportId": sample["reportId"], "wlId": sample["wlId"]},  # Filter criteria
                {"$set": sample},  # Update operation
                upsert=True  # Perform an upsert
            )
            operations.append(operation)

        #print(list(final_samples))
        # Perform the bulk upsert
        result = QASampleCol.bulk_write(operations)

        message += f', "GenStats":"Samples Existing={result.matched_count}; Samples Saved={result.upserted_count}"'

        message += "}"

        if len(unique_rep_ids) > 1 and ctr < len(unique_rep_ids):
            message += ","
            ctr += 1
        
        '''print()
        print(f"lnt: {len(unique_rep_ids)}")
        print()
        print(f"ctr: {ctr}")'''
    
        
    message += "}"
        
    # Your logic to generate samples using frRpId, toRpId, and sndTyp
    #message = f"Python Received: From Rep ID: {frRpId}, To Rep ID: {toRpId}, Send Type: {sndTyp}, Wlid: {wlIds}"
    print(message)

    # IF ALL PARAMETERS RECIVED = 0 then we will run for all avialable cohorts in imaging minus all allready processed
    # IF Any parameter is given the data will be fetched accordingly
    # Once data recived fetch seperate for email and paper for each report id and then get 25 random samples from paper and email each
    # Combine the above to create the complete set of 50 sample per report id
    # if any set has missing data i.e less or no email data found to randomly get 35 samples we need to complete for remaining data from other group

if __name__ == "__main__":
    main()