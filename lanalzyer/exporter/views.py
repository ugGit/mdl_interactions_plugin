from django.shortcuts import render
from django.http import HttpResponse
from django.template import loader

import requests

# Create your views here.

def index(request):
  context = {
    # TODO: should not be hardcoded at the end, or fetched from the session at least
    'moodle_url': 'http://localhost:8000',
    'username': 'teacher', 
    'password': 'teacher'
  }
  return render(request, 'exporter/connectionForm.html', context)

def courselist(request):
  moodle_url = request.POST.get("moodle_url", "")
  username = request.POST.get("username", "")
  password = request.POST.get("password", "")

  # get the web service token from moodle 
  webservice_name = 'wafed_webservices'
  url_auth = f'{moodle_url}/login/token.php?username={username}&password={password}&service={webservice_name}'
  response_auth = requests.post(url_auth).json()  

  # store it in the session of the user
  request.session['mdl_token'] = response_auth['token']
  request.session['mdl_url'] = moodle_url

  # fetch list of courses
  webservice_function_course_list = 'local_moodle_ws_la_trace_exporter_get_available_courses'
  url_course_list = f'{moodle_url}/webservice/rest/server.php?wstoken={request.session["mdl_token"]}&wsfunction={webservice_function_course_list}&moodlewsrestformat=json'
  response_course_list = requests.post(url_course_list).json()  
  print(response_course_list)

  context = {
    'course_list': response_course_list
  }
  return render(request, 'exporter/courseList.html', context)
