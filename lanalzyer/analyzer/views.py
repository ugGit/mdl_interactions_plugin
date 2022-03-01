from tkinter import E
from django.shortcuts import render
from django.http import HttpResponse
import numpy

import requests
import pickle

import pickle
import pandas as pd

# Create your views here.

def course(request, course_id):
  ''' Load the list which assigns events to categories and perform some pre-processing on it.'''
  def load_event_categories():
    filename_event_list = "../moodle_default_events_list.xlsx"
    event_list = pd.read_excel(filename_event_list, header=7)
    event_list = event_list.rename(columns = {"Extracted Event Name":"eventname", "Active/Passive":"activepassive", "User-Agent-Based":"useragentbased", "Novel Learning-Cycle":"newlc"})
    event_list = event_list[["eventname", "activepassive", "useragentbased", "newlc"]]
    # convert columns to strings for the events
    event_list_string_columns = ["eventname", "activepassive", "useragentbased", "newlc"]
    for c in event_list_string_columns:
      event_list[c] = event_list[c].astype('string')
    return event_list

  ''' Fetch the requested course log from the moodle instance.'''  
  def fetch_course_log_through_webservice():
    # fetch learning traces of course
    webservice_function_course_log = 'local_moodle_ws_la_trace_exporter_get_course_data'
    # TODO: the webservice function would support multiple courses at once
    url_course_log = f'{request.session["mdl_url"]}/webservice/rest/server.php?wstoken={request.session["mdl_token"]}&wsfunction={webservice_function_course_log}&moodlewsrestformat=json&courseids[0]={course_id}'
    return requests.post(url_course_log).json()  

  ''' Get all different categories from the original event list.'''
  def get_newlc_categories(event_list):
    newlc_categories = event_list[~event_list["newlc"].isna()]["newlc"].unique()
    newlc_categories = sorted(newlc_categories)
    return newlc_categories

  ''' Preprocess the data from the course log. This method is based on the pythoner notebook `category_mapper.ipynb`.'''
  def preprocess_course_log(unpreprocessed_course_log, event_list):
    course_log = unpreprocessed_course_log

    # convert columns to strings for the course log
    course_log_string_columns = ["action", "target", "crud", "eventname", "userrole"]
    for c in course_log_string_columns:
      course_log[c] = course_log[c].astype('string')

    # count the occurrence of each event type
    event_frequency = course_log.groupby(["userid","eventname"]).count()
    event_frequency = event_frequency.drop(labels=[c for c in event_frequency.columns[1:]], axis="columns")
    event_frequency = event_frequency.rename(columns = {"action":"count"})
    event_frequency = event_frequency.reset_index()

    # somehow the type gets lost when grouping on it, thus, redefine it
    event_frequency["eventname"] = event_frequency["eventname"].astype('string')

    # map events with interaction categories
    overview = pd.merge(event_frequency, event_list, how="left", on="eventname")

    # only get rows/events which have a category from the newlc (new learning cycle concept) assigned
    nan_mask = overview["newlc"].notnull()
    preprocessed_course_log = overview[nan_mask]
    
    return preprocessed_course_log

  ''' Calculate all the relevant metrics for the later visualizations. This method is based on the pythoner notebook `category_mapper.ipynb`.'''
  def evaluate_course_log(preprocessed_course_log):
    # get the sum of events for a single category per student
    newlc_per_student = preprocessed_course_log.groupby(["userid","newlc"]).sum()
    # insert 0 values for absent indices
    newlc_per_student = newlc_per_student.reindex( pd.MultiIndex.from_product([newlc_per_student.index.levels[0], 
    newlc_categories], names=['userid', 'newlc']), fill_value=0)
    # create pivot table with one row for each user
    newlc_per_student = newlc_per_student.unstack(level=1)
    # remove `count` as multicolumn name
    newlc_per_student.columns = newlc_per_student.columns.droplevel()

    # compute average data
    newlc_sum = preprocessed_course_log.groupby("newlc").sum()["count"]
    # insert 0 values for absent indices
    newlc_sum = newlc_sum.reindex(newlc_categories, fill_value=0)
    nbr_users = len(preprocessed_course_log["userid"].unique())
    newlc_average = newlc_sum.divide(nbr_users).values

    return newlc_per_student, newlc_average


  course_log_json = fetch_course_log_through_webservice()
  course_log = pd.DataFrame(course_log_json)
  course_log = course_log.astype({"relateduserid": int}, errors="ignore")
  event_list = load_event_categories()
  newlc_categories = get_newlc_categories(event_list)
  preprocessed_course_log = preprocess_course_log(course_log, event_list)
  newlc_category_sum_per_student, newlc_category_sum_average = evaluate_course_log(preprocessed_course_log)
  table_headers = course_log.head()

  # prepare options for filters
  filters = dict()
  for column in table_headers:
    filters[column] = list(course_log[column].unique())

  # remove the NaN value from the relateduserid column if present
  filters["relateduserid"] = [x for x in filters["relateduserid"] if str(x) != 'nan']

  # convert date values from numpy types (int64, float64, ndarrays) to pythons default datatypes
  for f in filters:
    test_element =filters[f][0]
    if isinstance(test_element, numpy.integer):
      filters[f] = [int(e) for e in filters[f]]
    elif isinstance(test_element, numpy.floating):
      filters[f] = [float(e) for e in filters[f]]
    elif isinstance(test_element, numpy.ndarray):
      filters[f] = [e.tolist() for e in filters[f]]
  

  context = {
    'log': course_log[-500:].to_json(orient="records"), # TODO: currently limited to 500 entries to speed up page loading
    'table_headers': table_headers,
    'filters': filters,
    'newlc_categories': newlc_categories,
    'newlc_category_sum_per_student': newlc_category_sum_per_student.to_dict(orient="index"),
    'newlc_category_sum_average': newlc_category_sum_average.tolist,
  }
  return render(request, 'analyzer/courseDetails.html', context)
