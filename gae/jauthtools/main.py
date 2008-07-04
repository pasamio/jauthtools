#!/usr/bin/env python

import wsgiref.handlers, os, time
import pprint

from google.appengine.api import users
from google.appengine.ext import webapp, db

def generate_hash():
	import sha, time
	return sha.new(str(time.time())+os.environ.get("REMOTE_ADDR")).hexdigest()


class RemoteUser(db.Model):
	remotesite = db.StringProperty()
	landingpage = db.StringProperty()
	localuser = db.UserProperty()
	lastseen = db.DateTimeProperty(auto_now_add=True)
	

class MainPage(webapp.RequestHandler):
	def noLandingPage(self):
		self.response.set_status(500,'No landing page')
		self.printHTMLMessage('Error: No landing page detected. <a href="javascript:history.go(-1)">Go back</a>. Please submit a landing page so that we can return you once you have passed customs.')
	
	def printHTMLMessage(self,message):
		self.response.out.write('<html><body><p>'+ message +'</p></body></html>')

	def get(self):
		user = users.get_current_user()
		self.response.headers['Content-Type'] = 'text/html'
		landingpage = self.request.get('landingpage')
		
		if user:
			# There is a user instance, so therefore they're logging in for a reason
			
			# Create a cookie
			# Check for a saved cookie entry
			ckSession = self.request.cookies.get('jauthsess')
			remoteuser = RemoteUser()
	
			ckKey = self.request.cookies.get("jauthkey")
			if ckKey != None:
				remoteuser = RemoteUser.get(ckKey)
				if remoteuser == None:
					if landingpage == '':
						self.noLandingPage()
						return
					else:
						remoteuser = RemoteUser()
						remoteuser.landingpage = landingpage
						ckKey = str(remoteuser.put())
			else:
				if landingpage == '':
					self.noLandingPage()
					return
				else:
					remoteuser = RemoteUser()
					remoteuser.landingpage = landingpage
					ckKey = str(remoteuser.put()) # get a key
					
			
			remoteuser.localuser = user
			remoteuser.put()
		
			if remoteuser.landingpage == None:
				self.printHTMLMessage('Error: No landing page')			
			else:
				self.response.out.write('<html><body><p>Welcome</p>')
				logoutUrl = users.create_logout_url('/')
				self.response.out.write('<p><a href="' + logoutUrl + '">Logout from Google</a></p>')
				nextHop= remoteuser.landingpage + '?jauthgooglekey='+ckKey
				self.response.out.write('<p><a href="' + nextHop +'">Continue Authentication</a></p>')
				self.response.out.write('</body></html>')
				self.redirect(nextHop)
		else:
			ckKey = self.request.get('retrkey')
			if ckKey != '':
				self.response.headers['Content-Type'] = 'text/xml' # override this
				self.response.out.write('<?xml version="1.0" encoding="utf-8"?>\n') # xml header
				try:
					remoteuser = RemoteUser.get(ckKey)
					if remoteuser != None:
						self.response.out.write('<response type="user">\n');
						self.response.out.write('<user nickname="' + remoteuser.localuser.nickname() + 
							'" domain="' + remoteuser.localuser.auth_domain() + 
							'" email="' + remoteuser.localuser.email() + '" />\n');
						self.response.out.write('</response>')
						#remoteuser.delete() # remove the instance once its been collected
					else:
						self.response.out.write('<response type="error">Invalid Key</response>')
				except Exception, e:
					self.response.out.write('<response type="error">Invalid Key</response>')
			else:
				jauthsess = generate_hash()
				remoteuser = RemoteUser()
				remoteuser.sessionhandle = jauthsess
				if landingpage == '': # no landing page?!?
					self.noLandingPage()
				else:
					remoteuser.landingpage = landingpage
					jauthkey = remoteuser.put()
					self.response.headers.add_header('Set-Cookie','jauthkey = ' + str(jauthkey))
					self.response.headers.add_header('Set-Cookie','jauthsess = ' + str(jauthsess))
					loginUrl = users.create_login_url(self.request.uri)
					self.response.out.write('<html><body><p>Redirecting to <a href="' + loginUrl + '">login</a></p></body></html>')
					self.redirect(loginUrl) # send the user automatically

def main():
	application = webapp.WSGIApplication(
										[('/', MainPage)],
										debug=True)
	wsgiref.handlers.CGIHandler().run(application)

if __name__ == "__main__":
	main()