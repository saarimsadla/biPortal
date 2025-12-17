import pymysql
import json
import sys

mysql_databases = [
    {
        "host": "localhost",
        "user": "sasadla",
        "password": "sasadla123*",
        "database": "cust_xcl",
        "wlId" : [46]
    },
    {
        "host": "localhost",
        "user": "sasadla",
        "password": "sasadla123*",
        "database": "cust_ent",
        "wlId" : [38]
    },
    {
        "host": "localhost",
        "user": "sasadla",
        "password": "sasadla123*",
        "database": "cust_mlt",
        "wlId" : [47,52]
    }
]

def get_db_config(wlId):
    for db in mysql_databases:
        if wlId in db['wlId']:
            return db
    return None

def main():
    try:
        # Read JSON input from stdin
        raw_input = sys.stdin.read().strip()
        data = json.loads(raw_input)

        wlId = int(data["wlId"])
        qry = data["qry"]

        db_config = get_db_config(wlId)
        if not db_config:
            print(json.dumps({"error": f"No database configuration found for wlId: {wlId}"}))
            sys.exit(1)

        connection = pymysql.connect(
            host=db_config['host'],
            user=db_config['user'],
            password=db_config['password'],
            database=db_config['database']
        )

        with connection.cursor() as cursor:
            cursor.execute(qry)
            columns = [desc[0] for desc in cursor.description]
            qryResult = cursor.fetchall()
            result_with_columns = [dict(zip(columns, row)) for row in qryResult]
            print(json.dumps(result_with_columns, default=str))

    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
