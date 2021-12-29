from django.urls import path

from . import views

urlpatterns = [
    path('', views.index, name='index'),
    path('courselist', views.courselist, name='courseList'),
]
