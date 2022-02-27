from django.urls import path

from . import views

app_name = 'exporter'

urlpatterns = [
    path('', views.index, name='moodleConnectionForm'),
    path('courselist', views.courselist, name='courseList'),
]
