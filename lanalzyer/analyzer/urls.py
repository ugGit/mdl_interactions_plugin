from django.urls import path

from . import views

app_name = 'analyzer'

urlpatterns = [
    path('course/<int:course_id>', views.course, name='course')
]
