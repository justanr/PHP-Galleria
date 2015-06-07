# A simple upload script to add pictures to a database.
# ARGV:
#   [0]: The script name
#   [1]: The packed ziparchive (named for it's directory: example.zip becomes example/)
#   [2]: The gallery directory
#   [3]: The name of the gallery

import os, zipfile, re
import MySQLdb as mdb
from PIL import Image
from fnmatch import fnmatch
from sys import argv
from time import time, sleep
from json import loads

class g_sql(object):
    def __init__(self, connFile = 'conn.json'):

        connDetails = self.getConnDetails(connFile)

        self.conn = mdb.connect(**connDetails)
        self.cursor = self.conn.cursor(mdb.cursors.DictCursor)

    def getConnDetails(self, connFile = 'conn.json'):
        fhConnFile = open(connFile)
        rjConnDetails = fhConnFile.read()
        fhConnFile.close()
        pjConnDetails = loads(rjConnDetails)
        pjConnDetails.pop('dsn')

        return pjConnDetails


    def checkPicStatus(self, file_view):
        stmn = self.cursor.execute("SELECT p_id FROM pictures WHERE file_view = %s", (file_view))

        if stmn:
            return True
        else:
            return False

    def checkGalStatus(self, g_dir):
        stmn = self.cursor.execute("SELECT g_id FROM galleries WHERE g_dir = %s", (g_dir))

        if stmn:
            return self.cursor.fetchone()
        else:
            return False

    def makeGallery(self, g_name, g_dir, g_url):
        sql = """
            INSERT INTO galleries (g_id, g_name, g_dir, g_url)
            VALUES (DEFAULT, %s, %s, %s);
        """
        try:
            stmn = self.cursor.execute(sql, (g_name, g_dir, g_url))
        except:
            self.conn.rollback()
        else:
            self.conn.commit()


    def insertPicInGal(self, g_id, file_view, file_thumb):
        sql = """
                INSERT INTO pictures (p_id, g_id, file_view, file_thumb, uploaded)
                VALUES (DEFAULT, %s, %s, %s, %s);
            """
        try:
            stmn = self.cursor.execute(sql, (g_id, file_view, file_thumb, int(time())))
        except:
            self.conn.rollback()
        else:
            self.conn.commit()


def extractall(packed, target):
    try:
        z = zipfile.ZipFile(packed)

        for name in z.namelist():
            (dirname, filename) = os.path.split(name)
            imgpath = os.path.join(target, filename)
            fd = open(imgpath, "w")
            fd.write(z.read(name))
            fd.close()
        return True

    except zipfile.error:
        return False
    finally:
        z.close()


def resizeImage(imageFile, galPath):
    thumb = Image.open(os.path.join(galPath, imageFile))
    full  = Image.open(os.path.join(galPath, imageFile))

    (name, ext) = os.path.splitext(imageFile)
    thumbname = "%s_thumb%s" % (name, ext)
    thumbHeight = 180
    fullHeight  = 600

    thumbHeightPercent = thumbHeight / float(thumb.size[1])
    fullHeightPercent  = fullHeight  / float(full.size[1])

    thumbWidth = int(thumbHeightPercent * thumb.size[0])
    fullWidth  = int(fullHeightPercent  * full.size[0])

    thumbSize = (thumbWidth, thumbHeight)
    fullSize  = (fullWidth,  fullHeight)


    try:
        thumb = thumb.resize(thumbSize, Image.ANTIALIAS)
        thumb.save(os.path.join(galPath, thumbname), "JPEG")
        full = full.resize(fullSize, Image.ANTIALIAS)
        full.save(os.path.join(galPath, imageFile), "JPEG")
        return thumbname
    except IOError:
        print "Cannot create thumbfile for %s" % imageFile
	raise

def main(argv):
    if len(argv) == 4:
        print "All arguements filled."
        g_dir = os.path.basename(argv[1]).split('.')[0]
        print "g_dir reads: %s" % (g_dir)
        target = os.path.join(argv[2], g_dir)
        print "full target reads: %s" % (target)
        if not os.path.exists(target):
            os.makedirs(target)

        if extractall(argv[1], target):
            print "extraction succeeded."
            db = g_sql()

            if db:
                print "connected to database."

                g_id = db.checkGalStatus(g_dir)

                if not g_id:
                    pattern = '[`~!@#$%^&*()\[\]{}:;\'"?/\\<>,.]'
                    print "the gallery isn't in the database, making it."
                    g_url = re.sub(pattern, '', argv[3].lower()).rstrip().replace(' ', '-')
                    print "args are: %s, %s, %s" % (argv[3], g_dir, g_url)
                    db.makeGallery(argv[3], g_dir, g_url)
                g_id = db.checkGalStatus(g_dir)
                print "g_id: %s" % (g_id)

                files = os.listdir(target)
                print files

                for f in files:
                    if not fnmatch(f, "*[_thumb].jpg") and not db.checkPicStatus(f):
                        print "%s is not a thumb already, making a thumb" % (f)
                        thumbname = resizeImage(f, target)
                        print "new thumbnail name: %s" % (thumbname)
                        print "inserting into kaitlyngarrett.pictures"
                        db.insertPicInGal(g_id['g_id'], f, thumbname)
                        sleep(1)
                    else:
                        print "Was image already a thumb? %s" % (fnmatch(f, "*[_thumb].jpg"))
                        print "Was image already in the gallery? %s" % (db.checkPicStatus(f))

        else:
            os.rmdir(target)
            print "Extraction failed."
            exit()

    else:
        print "upload expects three arguements, %d given." % (len(argv) - 1)

if __name__ == "__main__":
    main(argv)
