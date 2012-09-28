import json
import dateutil.parser
from collections import deque
#import threading
conn = None
cursor = None
FILE = None
LOG_FILE = None
ERROR_FILE = None
working_json_file_name = None

fb_user_ids = {}
message_ids = {}
fb_user_last_value = 0
message_last_value = 0
message_to_last_value = 0
likedby_last_value = 0
tag_last_value = 0
link_last_value = 0

new_fb_wall = []
new_fb_users = []
new_messages = []
new_message_tos = []
new_likedbys = []
new_tags = []
new_links= []

#to ignore unicode error:
def asciiCodify(s):
    try:
        if type(s) != unicode:
            s = unicode(s, errors="replace")
        v = s.encode("ascii", "replace")
        return v
    except Exception:
        return s 
    
# Return row_id if already exists or successful, else return -1 if exception occurs
def register_user (jsonObj):
    if jsonObj is None:
        return -1
    global fb_user_ids
    if jsonObj.has_key("id"):
        fb_id_str = (json.dumps(jsonObj["id"]).replace('\"','')).split('.')[0]
        fb_id = long(fb_id_str)
        if fb_user_ids.has_key(fb_id):
            row_id = fb_user_ids[fb_id]
            return row_id 
    else:
        return -1
    if jsonObj.has_key("name"):
        name = asciiCodify(json.dumps(jsonObj["name"]).replace('\"',''))
    else:
        name = None
    if jsonObj.has_key("category"):
        category = asciiCodify(json.dumps(jsonObj["category"]).replace('\"',''))
    else:
        category = None
    global fb_user_last_value
    fb_user_last_value = fb_user_last_value + 1
    fb_user_ids[fb_id] = fb_user_last_value
    new_fb_users.append((fb_user_last_value, fb_id_str, name, category))
    return fb_user_last_value

def register_tag (each_tag, message_row_id):
    user_row_id = register_user(each_tag)
    if each_tag.has_key("type"):
        type_ = asciiCodify(each_tag["type"].replace('\"',''))
    else:
        type_ = None
    if each_tag.has_key("offset"):
        offset = each_tag["offset"]
    else:
        offset = None
    if each_tag.has_key("length"):
        length = each_tag["length"]
    else:
        length = None
    global tag_last_value
    tag_last_value = tag_last_value + 1
    new_tags.append((tag_last_value, message_row_id, user_row_id, type_, offset, length))
    return user_row_id
        
# Parent_message is the Post on which the user commented, else itself if this is the Post 
def register_message (onedata, parent_message_row_id):
    global message_ids
    if onedata is None:
        return -1
    if onedata.has_key("id"):            
        message_id = onedata["id"].replace('\"','')
        if message_ids.has_key(message_id):
            return message_ids[message_id]
    else:
        return -1
    if onedata.has_key("name"):
        name = asciiCodify(onedata["name"].replace('\"',''))
    else:
        name = None
    if onedata.has_key("message"):
        text = asciiCodify(onedata["message"].replace('\"',''))
    else:
        text = None
    if onedata.has_key("type"):
        type_ = asciiCodify(onedata["type"].replace('\"',''))
    else:
        type_ = None
    if onedata.has_key("description"):
        description = asciiCodify(onedata["description"].replace('\"',''))
    else:
        description = None
    if onedata.has_key("caption"):
        caption = asciiCodify(onedata["caption"].replace('\"',''))
    else:
        caption = None
    if onedata.has_key("created_time"):
        created_time = dateutil.parser.parse(onedata["created_time"].replace('\"',''))
    else:
        created_time = None
    if onedata.has_key("updated_time"):
        updated_time = dateutil.parser.parse(onedata["updated_time"].replace('\"',''))
    else:
        updated_time = None        
    if onedata.has_key("can_remove"):
        can_remove = onedata["can_remove"]
    else:
        can_remove = None
    if onedata.has_key("shares"):
        shares_count = onedata["shares"]["count"]
    else:
        shares_count = None    
    if onedata.has_key("likes"):
        if type(onedata["likes"]) is dict:
            if onedata["likes"].has_key("count"):
                likes_count = onedata["likes"]["count"]
            else:
                likes_count = None              
        elif type(onedata["likes"]) is int:
            likes_count = onedata["likes"]
        else:
            likes_count = None 
    else:
        likes_count = None 
    if onedata.has_key("comments"):
        comments_count = onedata["comments"]["count"]
    else:
        comments_count = None
    if onedata.has_key("from"):
        postedBy_row_id = register_user(onedata["from"])
        if postedBy_row_id == -1:
            return -1
    else:
        return -1
    global message_last_value
    message_last_value = message_last_value + 1
    message_ids[message_id] = message_last_value
    message_row_id = message_last_value
    
    if onedata.has_key("to"):
        for each_to in onedata["to"]["data"]:
            if each_to is None:
                continue
            postedTo_row_id = register_user(each_to)
            global message_to_last_value
            message_to_last_value = message_to_last_value + 1
            new_message_tos.append((message_to_last_value, message_row_id, postedTo_row_id))

    if onedata.has_key("story_tags"):
        if type(onedata["story_tags"]) is list:
            if type(onedata["story_tags"][0]) is list:
                for each_tag in onedata["story_tags"][0]:
                    if each_tag is None:
                        continue
                    register_tag(each_tag, message_row_id)
        else:
            list_of_tag_keys = onedata["story_tags"].keys()
            for each_tag_key in list_of_tag_keys:
                for each_tag in onedata["story_tags"][each_tag_key]:
                    if each_tag is None:
                        continue
                    register_tag(each_tag, message_row_id)

    if onedata.has_key("message_tags"):
        if type(onedata["message_tags"]) is list:
            if type(onedata["message_tags"][0]) is list:
                for each_tag in onedata["message_tags"][0]:
                    if each_tag is None:
                        continue
                    register_tag(each_tag, message_row_id)
            else:
                for each_tag in onedata["message_tags"]:
                    if each_tag is None:
                        continue
                    register_tag(each_tag, message_row_id)
                
        else:
            list_of_tag_keys = onedata["message_tags"].keys()
            for each_tag_key in list_of_tag_keys:
                for each_tag in onedata["message_tags"][each_tag_key]:
                    if each_tag is None:
                        continue
                    register_tag(each_tag, message_row_id)      

    group_id = fb_user_ids[long(message_id.split('_')[0])]
    if parent_message_row_id is None:
        new_messages.append((message_last_value, message_id, message_last_value, group_id, name, text, type_, description, caption, postedBy_row_id, created_time, updated_time, can_remove, shares_count, likes_count, comments_count))
    else:
        new_messages.append((message_last_value, message_id, parent_message_row_id, group_id, name, text, type_, description, caption, postedBy_row_id, created_time, updated_time, can_remove, shares_count, likes_count, comments_count))
    
    global link_last_value
    if onedata.has_key("picture"):
        link_last_value = link_last_value +1
        new_links.append((link_last_value, message_row_id, asciiCodify(onedata["picture"]), 'picture', None, None))
    if onedata.has_key("link"):
        link_last_value = link_last_value +1
        new_links.append((link_last_value, message_row_id, asciiCodify(onedata["link"]), 'link', None, None))
    if onedata.has_key("icon"):
        link_last_value = link_last_value +1
        new_links.append((link_last_value, message_row_id, asciiCodify(onedata["icon"]), 'icon', None, None))
    if onedata.has_key("actions"):
        for each_action in onedata["actions"]:
            if each_action.has_key("link"):
                link_last_value = link_last_value +1
                new_links.append((link_last_value, message_row_id, asciiCodify(each_action["link"]), 'ACTION', asciiCodify(each_action["name"]), None))
    if onedata.has_key("properties"):
        for each_property in onedata["properties"]:
            if each_property.has_key("href"):
                each_property_href = asciiCodify(each_property["href"])
            else:
                each_property_href = None
            if each_property.has_key("name"):
                each_property_name = asciiCodify(each_property["name"])
            else:  
                each_property_name = None
            if each_property.has_key("text"):
                each_property_text = asciiCodify(each_property["text"])
            else:
                each_property_text = None
            link_last_value = link_last_value +1
            new_links.append((link_last_value, message_row_id, each_property_href, 'PROPERTY', each_property_name, each_property_text))

    return message_row_id

def parse_this_post (json_strings):
    comment_message_ids = {}
    likedby_row_ids = {}
    global likedby_last_value                                
    message_row_id = -1
    likes_count = 0
    comments_count = 0
    likes_of_comments_count_queue = deque([])
    comment_message_row_id_queue = deque([])
    for oneline in json_strings:
#        print oneline
        if oneline != '\n':
            try:
                onedata = json.loads(oneline)
            except Exception as e:
                ERROR_FILE.write("*********Json Loading at Json File %s************Error %s\n" % (working_json_file_name, e.args[0])) 
                return -1        
#            FILE.write(json.dumps(onedata, sort_keys=True, indent = 4))
            if onedata is None:
                continue
            if onedata is False:
                continue
            if type(onedata) is not dict:
                continue
            if onedata.has_key("id"):
                if message_ids.has_key(onedata["id"]):
#                    ERROR_FILE.write("*********Duplicate crawling %s************\n" % (working_json_file_name))
                    return -1
                message_row_id = register_message(onedata, None)
                if message_row_id == -1:
                    return -1
                if onedata.has_key("likes"):
                    likes_count = onedata["likes"]["count"]
                if onedata.has_key("comments"):
                    comments_count = onedata["comments"]["count"]
            else:
                if onedata.has_key("ep_likes"):
                    if onedata["ep_likes"].has_key("data"):
                        for each_like in onedata["ep_likes"]["data"]:
                            if likes_count > 0:
                                likedby = register_user(each_like)
                                if (likedby_row_ids.has_key(likedby)):
                                    continue
                                likes_count -= 1
                                likedby_row_ids[likedby] = 1 #dummy value
                                likedby_last_value = likedby_last_value + 1
                                new_likedbys.append((likedby_last_value, message_row_id, likedby))
                        if onedata["ep_likes"].has_key("paging"):
                            if not onedata["ep_likes"]["paging"].has_key("next"):
                                if onedata["ep_likes"]["paging"].has_key("previous"):
                                    if likes_count > 0:
                                        LOG_FILE.write('Inconsistency of %s likes at %s \n' %(likes_count, working_json_file_name))
                        else:
                            if likes_count > 0:
                                LOG_FILE.write('Inconsistency of %s likes at %s \n' %(likes_count, working_json_file_name))
                        
                            
                if onedata.has_key("ec_comments"):
                    if onedata["ec_comments"].has_key("data"):
                        for each_comment in onedata["ec_comments"]["data"]:
                            if comments_count > 0:
                                if comment_message_ids.has_key(each_comment["id"]):
                                    continue
                                comment_message_ids[each_comment["id"]] = 1 #dummy value
                                comments_count -= 1
                                if each_comment.has_key("likes"):
                                    likes_of_comments_count_queue.append(each_comment["likes"])
                                else:
                                    likes_of_comments_count_queue.append(0)
                                comment_message_row_id_queue.append(register_message(each_comment, message_row_id))
                        if onedata["ec_comments"].has_key("paging"):
                            if not onedata["ec_comments"]["paging"].has_key("next"):
                                if onedata["ec_comments"]["paging"].has_key("previous"):
                                    if comments_count > 0:
                                        LOG_FILE.write('Inconsistency of %s comments at %s \n' %(comments_count, working_json_file_name))
                        else:
                            if comments_count > 0:
                                LOG_FILE.write('Inconsistency of %s comments at %s \n' %(comments_count, working_json_file_name))

                if onedata.has_key("ec_likes"):
                    if onedata["ec_likes"].has_key("data"):
                        try:
                            likes_of_a_comment_count = likes_of_comments_count_queue.popleft()
                            comment_message_row_id = comment_message_row_id_queue.popleft()
                            for each_like_on_comment in onedata["ec_likes"]["data"]:
                                if likes_of_a_comment_count > 0:
                                    likes_of_a_comment_count -= 1
                                    likedby = register_user(each_like_on_comment)
                                    likedby_last_value = likedby_last_value + 1                                    
                                    if comment_message_row_id != -1:    # Else skip appending new likedby row
                                        new_likedbys.append((likedby_last_value, comment_message_row_id, likedby))
                            if onedata["ec_likes"].has_key("paging"):
                                if onedata["ec_likes"]["paging"].has_key("next"):
                                    likes_of_comments_count_queue.appendleft(likes_of_a_comment_count)
                                    comment_message_row_id_queue.appendleft(comment_message_row_id)
                                else:
                                    if onedata["ec_likes"]["paging"].has_key("previous"):
                                        if likes_of_a_comment_count > 0:
                                            LOG_FILE.write('Inconsistency of %s likes within comments at %s \n' %(likes_of_a_comment_count, working_json_file_name))
                            else:
                                if likes_of_a_comment_count > 0:
                                    LOG_FILE.write('Inconsistency of %s likes within comments at %s \n' %(likes_of_a_comment_count, working_json_file_name))
                        except:
                            LOG_FILE.write('Inconsistency of UNKNOWN likes within comments at %s \n' %(working_json_file_name))
