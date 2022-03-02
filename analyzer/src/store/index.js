import { createStore } from "vuex";

export default createStore({
  state: {
    moodleToken: "",
    moodleUrl: "",
    moodleCurrentCourse: {},
    eventMappings: {},
  },
  getters: {},
  mutations: {
    setMoodleToken(state, token) {
      state.moodleToken = token;
    },
    setMoodleUrl(state, url) {
      state.moodleUrl = url;
    },
    setMoodleCurrentCourse(state, course) {
      state.moodleCurrentCourse = course;
    },
    setEventMappings(state, ev) {
      state.eventMappings = ev;
    },
  },
  actions: {},
  modules: {},
});
