#!/usr/local/bin/python

#Three Python packets it needs: numpy,scipy,igraph
import warnings
import sys
try:
    from igraph import Graph
    from scipy.cluster.vq import kmeans2
    from scipy.linalg import eigh
    import os
    import MySQLdb as mdb
    import cgi
    import numpy as np
    import simplejson as json

    import logging
    logging.basicConfig(filename='example.log', level=logging.DEBUG)
except BaseException as e:
    print 'Status: 500 Internal Server Error\r\n\r\n'
    print >> sys.stderr, e
    raise SystemExit

print("Content-Type: text/html\n\n")
#print("Content-Type: text/plain\n")

form = cgi.FieldStorage()
pageID = form.getfirst("page", 0)
postID = form.getfirst("post", 0)
numOpinions = form.getfirst("numOpinions", 2)

status = {'modularity': 0, 'message': "Ok",
          'id': str(pageID) + '_' + str(postID)}

if pageID == 0 or postID == 0:
    #print "Wrong parameters.."
    status['message'] = "Wrong parameters"
    print json.dumps({'status': status})
    exit(1)


#From http://wersdoerfer.com/~jochen/s9y/index.php?/archives/2009/11.html
def cluster_points(L):
    evals, evcts = eigh(L)
    evals, evcts = evals.real, evcts.real
    edict = dict(zip(evals, evcts.transpose()))
    evals = sorted(edict.keys())
    for k in range(0, len(evals)):
        if evals[k] > 1e-10:
            startfrom = k
            break
    H = np.array([edict[k] for k in evals[startfrom:startfrom + 2]])
    Y = H.transpose()
    warnings.simplefilter('ignore', UserWarning)
    returnIdx = map(int, kmeans2(Y, 2, iter=10,minit='random')[1])
    mod = gr.modularity(returnIdx)
    for w in range(0,200):
        idx = map(int, kmeans2(Y, 2, iter=10,minit='random')[1])
        if(gr.modularity(idx) > mod):
            mod = gr.modularity(idx)
            returnIdx = idx
    return mod, returnIdx

#-----------------------------------------------------------------------------
Modularity_Threshold = 0.2

con = mdb.connect('localhost', 'sincere-read', '', 'sincere')
cur = con.cursor(mdb.cursors.DictCursor)
cur.execute('select comment.id as CommentID, likedby.fb_id as LikeFromID, '
            'comment.fb_id as LikeToID from comment, likedby '
            'WHERE likedby.page_id=' + con.escape_string(pageID) + ' AND '
            'likedby.post_id=' + con.escape_string(postID) + ' AND '
            'comment.id = likedby.comment_id AND '
            'comment.page_id = likedby.page_id AND '
            'comment.post_id = likedby.post_id;')
rawlikedata = cur.fetchall()

if not rawlikedata:
    status['message'] = "No comments found"
    print json.dumps({'status': status})
    exit(1)

NodeList = {}
for line in rawlikedata:
    NodeList[line['LikeFromID']] = 1
    NodeList[line['LikeToID']] = 1


gr = Graph(0)

gr.add_vertices(len(NodeList))
#Now, we finish adding nodes.
#-------------------------------------
count = 0
FromNodeIDtoVerticeID = {}
for vertice, one in NodeList.iteritems():
    FromNodeIDtoVerticeID[vertice] = count
    # In gr.vs, each vertice has two characters.
    gr.vs[count]['verticeID'] = count
    gr.vs[count]['NodeID'] = str(vertice)
    count += 1
#print 'map from NodeID to verticeID: '
#print FromNodeIDtoVerticeID
#Now we map long NodeID into VerticeID.

for rawlikedata_line in rawlikedata:
    gr.add_edges((
        FromNodeIDtoVerticeID[rawlikedata_line['LikeFromID']],
        FromNodeIDtoVerticeID[rawlikedata_line['LikeToID']]))
#print summary(gr)

edgelist = gr.get_edgelist()
#Now we finish building edges.

length = len(NodeList)
#print 'nodelist: ' + str(length)


OneGroup = {}
AnotherGroup = {}
#We should make sure that there is at least one node in the list.
if not NodeList:
    status['message'] = "Empty NodeList"
    print json.dumps({'status': status})
    exit(1)

#print '-----------------'
#Now we are building adjacent matrix for later clustering.
b = np.arange(0, length * length, 1)

for i in range(0, length * length):
    b[i] = 0
#print b

b.shape = length, length

for i in range(0, len(edgelist)):
    b[edgelist[i][0]][edgelist[i][1]] = b[edgelist[i][0]][edgelist[i][1]] + 1
    b[edgelist[i][1]][edgelist[i][0]] = b[edgelist[i][1]][edgelist[i][0]] + 1
#Now we finished building adjacent matrix.

a = [sum(bi) for bi in b]

G = np.diag(a)
L = G - b
checkmodularity, membership = cluster_points(L)

#----------------------------------------------------------------------------
#Actually, it is from VerticeIDtoNodeID.
OrderIDtoUserID = {}
for line in FromNodeIDtoVerticeID:
    OrderIDtoUserID[FromNodeIDtoVerticeID[line]] = line
if checkmodularity > Modularity_Threshold:
    #Get our results:
    for i in range(0, len(membership)):
        if membership[i] == 0:
            OneGroup[OrderIDtoUserID[i]] = gr.degree(i)
        else:
            AnotherGroup[OrderIDtoUserID[i]] = gr.degree(i)
else:
    for i in range(0, len(membership)):
        OneGroup[OrderIDtoUserID[i]] = gr.degree(i)


#if checkmodularity > Modularity_Threshold:
#	print 'modularity value is :' + str(checkmodularity)
#	print OneGroup
#	print AnotherGroup
#else:
#	print 'modularity value is :' + str(checkmodularity)
#	print OneGroup

#FER added code.
#Include deliberaton and Bias
influence = {'Bias': [], 'Deliberation': []}
if(os.path.isfile('../lists/' + str(pageID) + '.json')):
    influence = json.loads(open('../lists/' +
                                str(pageID) + '.json', 'r').read())

#Our result array.
result = []
addedComments = []
#Enumerate over our groups.
for (i, group) in enumerate([OneGroup, AnotherGroup]):
    result.append([])
    if len(group) is 0:
        sql = "SELECT message, fb_id, name, page_id, post_id," \
            " comment.id AS id, UNIX_TIMESTAMP(created_time) as timestamp" \
            "FROM comment, fb_user"\
            " WHERE page_id=" + con.escape_string(pageID) + " AND post_id=" + \
            con.escape_string(postID) + " AND comment.fb_id=fb_user.id"
        cur.execute(sql)
        for row in cur.fetchall():
            if row['id'] in addedComments:
                continue
            addedComments.append(row['id'])
            if row['fb_id'] in influence['Bias']:
                row['user_mode'] = -1
            elif row['fb_id'] in influence['Deliberation']:
                row['user_mode'] = 1
            else:
                row['user_mode'] = 0
            row['ranking'] = -1
            result[0].append(row)
            break
    inList = ""
    for keys in group.keys():
        inList += "%d, " % keys
    sql = "SELECT message, fb_id, name, page_id, post_id, comment.id AS id, " \
          "UNIX_TIMESTAMP(created_time) as timestamp FROM comment, fb_user " \
          "WHERE page_id=" + con.escape_string(pageID) + " AND post_id=" + \
          con.escape_string(postID) + " AND fb_id IN (" + inList[0:-2] + ")" +\
          " AND comment.fb_id=fb_user.id"  # ORDER BY created_time desc;"
    try:
        cur.execute(sql)
    except:
        print('\nMysql error')
        sys.exit(1)
    for row in cur.fetchall():
            if row['id'] in addedComments:
                continue
            addedComments.append(row['id'])
            if row['fb_id'] in influence['Bias']:
                row['user_mode'] = -1
            elif row['fb_id'] in influence['Deliberation']:
                row['user_mode'] = 1
            else:
                row['user_mode'] = 0
            row['ranking'] = group[row['fb_id']]
            result[i].append(row)

#Remove duplicates
#result = list(set(result))

#Nice json print-out
status['modularity'] = checkmodularity
s = json.dumps({'status': status, 'comments': result, 'influence': influence},
               sort_keys=False, indent=2 * ' ')
print('\n'.join([l.rstrip() for l in s.splitlines()]))

#generate ugly html..
#cur.execute("SELECT message FROM post
#WHERE id="+postID+" AND page_id="+pageID)
#row = cur.fetchall()
#print '<div style="font-family: \'lucida grande\',
#tahoma, verdana, arial, sans-serif;font-size: 11px;">'
#print row[0]['message']
#print '<br/><i>'
#print checkmodularity
#print '</i>'
#print '</div>'
#width=700/len(result)
#for group in result:
#    print '<div style="width: '+ str(width) +
#'px;border: 1px solid darkblue;float: left;">'
##    print group
#    for comment in group.values():
#        print '<div style="border: 1px solid lightblue;font-family:
#\'lucida grande\', tahoma, verdana, arial, sans-serif;font-size: 11px;">'
##        print comment
##        print commentID['message']
#        print comment['d']['message']
#        print '</div>'
#    print '</div>'
