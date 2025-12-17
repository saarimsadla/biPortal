"""
authConfirm.py
Exact Python equivalent of authConfirm.go with identical JSON output.
Usage:
python3 authConfirm.py <username> <password>
Output:
Prints and writes the same JSON as the Go program to lgnRes.txt
"""

import sys
import os
import pymongo
import json

DB_URI = "mongodb://sasadla:sasadla123*@localhost:27017/"
DB_NAME = "hursPortal"

def connect_to_mongo():
    """Connect to MongoDB"""
    client = pymongo.MongoClient(DB_URI)
    return client, client[DB_NAME]

def authenticate_user(collection, username, password):
    """Authenticate user same as Go logic."""
    user = collection.find_one({"username": username})

    if not user:
        return {"User_Found": "N"}, 0, ""

    uid = user.get("_id", "")
    name = user.get("name", "")
    stored_pass = user.get("password", "")
    def_white_label = user.get("defWhitLabel", "")

    if name == "" or stored_pass == "" or def_white_label == "":
        return {
            "User_Found": "Y",
            "Password_Matched": "Error converting user data"
        }, 0, str(uid)

    if password == stored_pass:
        return {
            "User_Found": str(name),
            "Password_Matched": "Y",
            "defWhiteLabel": str(def_white_label)
        }, 1, str(uid)

    return {
        "User_Found": str(name),
        "Password_Matched": "N"
    }, 0, str(uid)


def get_user_rights(items_col, roles_col, uid):
    """Get rights and white labels, same as Go."""
    active_items = list(items_col.find({"active": "Y"}))
    item_ids = [itm["_id"] for itm in active_items if "_id" in itm]
    # print(item_ids)

    try:
        uid_int = int(uid)
    except Exception:
        return {"Error": "Invalid UID conversion"}, 0

    cursor = roles_col.find({
        "itemID": {"$in": item_ids},
        "uid": uid_int,
        "Active": "Y"
    })

    #print(cursor)

    white_labels = set()
    rights = set()

    for doc in cursor:
        wl = str(doc.get("whitelabel", ""))
        it = str(doc.get("itemID", ""))
        if wl:
            white_labels.add(wl)
        if it:
            rights.add(it)

    wl_str = ", ".join(sorted(white_labels))
    rights_str = ", ".join(sorted(rights))

    rolse_json = f'"rolse":[{{"whiteLabels":"{wl_str}"}},{{"rights":"{rights_str}"}}]'
    return rolse_json, 1


def main():
    if len(sys.argv) < 3:
        print("Usage: python authConfirm.py <username> <password>")
        sys.exit(1)

    username, password = sys.argv[1], sys.argv[2]
    user_table = "actor"
    item_table = "scripts"
    role_table = "roles"

    client, db = connect_to_mongo()

    try:
        actor_col = db[user_table]
        res, stat, uid = authenticate_user(actor_col, username, password)

        # Build base JSON manually (no Python dict -> JSON conversion)
        base_json = f'{{"User_Found":"{res.get("User_Found","")}"'
        if "Password_Matched" in res:
            base_json += f',"Password_Matched":"{res["Password_Matched"]}"'
        if "defWhiteLabel" in res:
            base_json += f',"defWhiteLabel":"{res["defWhiteLabel"]}"'

        # Append roles
        if stat == 1:
            item_col = db[item_table]
            role_col = db[role_table]
            rolse_json, st = get_user_rights(item_col, role_col, uid)
            if st == 1:
                base_json += f',{rolse_json}}}'
            else:
                base_json += ',"rolse":[{}]}'
        else:
            base_json += ',"rolse":[{}]}'

        # Write and print final result
        with open("lgnRes.txt", "w", encoding="utf-8") as f:
            f.write(base_json)

        print(base_json)

    finally:
        client.close()


if __name__ == "__main__":
    main()