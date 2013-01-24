#!/usr/local/bin/python

#Three Python packets it needs: numpy,scipy,igraph
#Input file:
#	    	Like_file: ['PostID','LikeFromID','LikeToID']
#Output:
#		Modularity Value
#		UserID for OneGroup
#		UserID for AnotherGroup

#import csv
#import scipy.stats as st
from igraph import * ## silence pyflakes
from scipy.cluster.vq import kmeans2
from scipy.linalg import eigh
import MySQLdb as mdb
import cgi
import numpy as np
import simplejson as json
import warnings

#Supress all warnings :)
warnings.simplefilter('ignore')

#print("Content-Type: text/html\n\n")
print("Content-Type: text/plain\n")

form = cgi.FieldStorage()
pageID = form.getfirst("page",0);
postID = form.getfirst("post",0);

if pageID == 0 or postID == 0:
    print "Wrong parameters.."
    exit()


def rename_clusters(idx):
    # so that first cluster has index 0

    num = -1
    seen = {}
    newidx = []
    for id in idx:
        if id not in seen:
            num += 1
            seen[id] = num
        newidx.append(seen[id])
    return np.array(newidx)

def cluster_points(L):

    evals, evcts = eigh(L)
    evals, evcts = evals.real, evcts.real
    edict = dict(zip(evals, evcts.transpose()))
    evals = sorted(edict.keys())

    for k in range(0,len(evals)):
	if evals[k] > 1e-10:
		startfrom = k
		break
#    print 'startfrom: ', str(evals[k])

    H = np.array([edict[k] for k in evals[startfrom:startfrom+2]])
    Y = H.transpose()
    res, idx = kmeans2(Y, 2, minit='random')

    return evals[:10000000000], Y, rename_clusters(idx)

#----------------------------------------------------------------------------------------------------------------------------------------
#rawlikefile = open('282370021775789_291368864209238-short.csv','r')
Modularity_Threshold = 0.2

#PostID = '282370021775789_349187595101578'#about Paul#b
#PostID = '282370021775789_291368864209238'#attitude about LA police#c
#PostID = '282370021775789_187307108026795'#one part#d
#PostID = '282370021775789_384417974904326'#almost two parts

#rawlikedata = csv.DictReader(rawlikefile,['CommentID','LikeFromID','LikeToID'])

#postID = '291368864209238'
#pageID = '282370021775789'
con = mdb.connect('localhost', 'sincere-read', '', 'sincere')
cur = con.cursor(mdb.cursors.DictCursor)
cur.execute('select comment.id as CommentID, likedby.fb_id as LikeFromID, comment.fb_id as LikeToID from comment, likedby where likedby.page_id='+pageID+' AND likedby.post_id='+postID+' AND comment.id = likedby.comment_id AND comment.page_id=likedby.page_id AND comment.post_id=likedby.post_id;')
rawlikedata = cur.fetchall()


NodeList = []
#rawlikefile.seek(0)
for rawlikedata_line in rawlikedata:
	if rawlikedata_line['LikeFromID'] not in NodeList:
		NodeList.append(rawlikedata_line['LikeFromID'])
	if rawlikedata_line['LikeToID'] not in NodeList:
		NodeList.append(rawlikedata_line['LikeToID'])

#print NodeList

gr = Graph(0)

gr.add_vertices(len(NodeList))
#Now, we finish adding nodes.
#print 'num of node: '
#print len(NodeList)
#-------------------------------------
count = 0
FromNodeIDtoVerticeID = {}
for vertice in NodeList:
	FromNodeIDtoVerticeID[vertice] = count
	# In gr.vs, each vertice has two characters.
	gr.vs[count]['verticeID'] = count
	gr.vs[count]['NodeID'] = str(vertice)
	count += 1
#print 'map from NodeID to verticeID: '
#print FromNodeIDtoVerticeID
#Now we map long NodeID into VerticeID.
#-------------------------------------
count = 0
for line in FromNodeIDtoVerticeID:
	count = count + 1
#print count
#-------------------------------------

#rawlikefile.seek(0)

for rawlikedata_line in rawlikedata:
	igraphEdgePair = (FromNodeIDtoVerticeID[rawlikedata_line['LikeFromID']],FromNodeIDtoVerticeID[rawlikedata_line['LikeToID']])
	gr.add_edges(igraphEdgePair)
#print summary(gr)

edgelist = gr.get_edgelist()
#Now we finish building edges.

length = len(NodeList)
#print 'nodelist: ' + str(length)


OneGroup = {}
AnotherGroup = {}
checkmodularity = -100
#We should make sure that there is at least one node in the list.
if length != 0:
	#print '-----------------'
	#Now we are building adjacent matrix for later clustering.
	b = np.arange(0,length*length,1)

	for i in range(0,length*length):
		b[i] = 0
	#print b

	b.shape = length,length

	for i in range(0,len(edgelist)):
		b[edgelist[i][0]][edgelist[i][1]] = b[edgelist[i][0]][edgelist[i][1]] + 1
		b[edgelist[i][1]][edgelist[i][0]] = b[edgelist[i][1]][edgelist[i][0]] + 1
	#Now we finished building adjacent matrix.

	a = [sum(bi) for bi in b]

	G = np.diag(a)
	L = G - b
	#---------------------------------------------------------------------------------------------------------------------------------

	checkmodularity = -100
	savemembership = []

	for w in range(0,200):
		evals, Y, idx = cluster_points(L)

		membership = []

		for i in range(0,len(idx)):
			membership.append(str(idx[i]))
			membership[i] = int(membership[i])

		if gr.modularity(membership) > checkmodularity:
			checkmodularity = gr.modularity(membership)
			savemembership = membership

	membership = savemembership
	#---------------------------------------------------------------------------------------------------------------------------------
	#Actually, it is from VerticeIDtoNodeID.
	OrderIDtoUserID = {}
	for line in FromNodeIDtoVerticeID:
		OrderIDtoUserID[FromNodeIDtoVerticeID[line]] = line
	if checkmodularity > Modularity_Threshold:
		#Get our results:
		for i in range(0,len(membership)):
			if membership[i] == 0:
				OneGroup[OrderIDtoUserID[i]] = gr.degree(i)

			else:
				AnotherGroup[OrderIDtoUserID[i]] = gr.degree(i)
	else:
		for i in range(0,len(membership)):
			OneGroup[OrderIDtoUserID[i]] = gr.degree(i)


#if checkmodularity > Modularity_Threshold:
#	print 'modularity value is :' + str(checkmodularity)
#	print OneGroup
#	print AnotherGroup
#else:
#	print 'modularity value is :' + str(checkmodularity)
#	print OneGroup
#
#FER added code.
#Our result array.
result=[{},{}]
#Enumerate over our groups.
for (i, group) in enumerate([OneGroup, AnotherGroup]):
    inList=""
    for keys in group.keys():
        inList += "%d, " % keys

    sql="SELECT message, fb_id, page_id, post_id, id, created_time FROM comment WHERE page_id="+pageID+" AND post_id="+postID+" AND fb_id IN ("+inList[0:-2]+");"
    cur.execute(sql)
    for row in cur.fetchall():
        row['created_time'] = row['created_time'].isoformat()
        result[i]["%d" % row['id']] = { 'ranking':group[row['fb_id']],
            'd':row}


s =json.dumps({'modularity': str(checkmodularity) ,'comments':result},  sort_keys=True,indent=2 * ' ')
print('\n'.join([l.rstrip() for l in  s.splitlines()]))




