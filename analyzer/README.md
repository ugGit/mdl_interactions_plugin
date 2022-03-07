# analyzer

This tool works only with a accordingly configured Moodle instance. It needs the `ws_la_trace_exporter` web service plugin installed and an external web service exposed with the following three functions:

- `gradereport_user_get_grade_items`
- `local_moodle_ws_la_trace_exporter_get_available_courses`
- `local_moodle_ws_la_trace_exporter_get_course_data`

Obviously, the Moodle instance must allow to create web tokens and expose web services through the REST API.

## Project setup

```
npm install
```

### Compiles and hot-reloads for development

```
npm run serve
```

### Compiles and minifies for production

```
npm run build
```

### Lints and fixes files

```
npm run lint
```

### Customize configuration

See [Configuration Reference](https://cli.vuejs.org/config/).
