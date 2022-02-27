from django.shortcuts import render
from django.http import HttpResponse

import requests
import pickle

# Create your views here.

def course(request, course_id):
  def fetch_course_details_through_webservice():
    # fetch learning traces of course
    webservice_function_course_details = 'local_moodle_ws_la_trace_exporter_get_course_data'
    # TODO: the webservice function would support multiple courses at once
    url_course_details = f'{request.session["mdl_url"]}/webservice/rest/server.php?wstoken={request.session["mdl_token"]}&wsfunction={webservice_function_course_details}&moodlewsrestformat=json&courseids[0]={course_id}'
    return requests.post(url_course_details).json()  
  
  def save_course_details_to_file(course_details, filename="course_log.pkl"):
    with open(filename, 'wb') as f:
      # Pickle the 'data' dictionary using the highest protocol available.
      pickle.dump(course_details, f, pickle.HIGHEST_PROTOCOL)

  def load_course_details_through_file(filename="course_log.pkl"):
    with open(filename, 'rb') as f:
      # The protocol version used is detected automatically, so we do not
      # have to specify it.
      return pickle.load(f)

  # TODO: decide where to fetch data from
  course_details = fetch_course_details_through_webservice();
  # save_course_details_to_file(course_details)
  # course_details = load_course_details_through_file()

  context = {
    'log': course_details[-500:], # TODO: currently limited to 500 entries to speed up page loading
    'table_headers': course_details[0].keys()
  }
  return render(request, 'analyzer/courseDetails.html', context)
