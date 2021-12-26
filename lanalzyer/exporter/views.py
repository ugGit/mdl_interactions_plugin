from django.shortcuts import render
from django.http import HttpResponse
from django.template import loader

# Create your views here.

def index(request):
  context = {
    'moodle_url': 'http://localhost:8000',
    'username': 'user', 
    'password': 'bitnami'
  }
  return render(request, 'exporter/connectionForm.html', context)

def selectCourseForm(request):
  test = request.POST.get("moodle_url", "")

  # setup plugin in my moodle instance

  # establish connection to moodle

  # fetch list of courses


  return HttpResponse(test + "what")
