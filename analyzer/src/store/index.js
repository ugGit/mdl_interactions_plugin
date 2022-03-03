import { createStore } from "vuex";

export default createStore({
  state: {
    moodleToken: "",
    moodleUrl: "",
    moodleCurrentCourse: {},
    eventMappings: {},
    eventCategories: [],
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
      const ec = [];
      for (let i = 0; i < state.eventMappings.length; i++) {
        if (
          state.eventMappings[i].newlc &&
          !ec.includes(state.eventMappings[i].newlc)
        ) {
          ec.push(state.eventMappings[i].newlc);
        }
      }
      state.eventCategories = ec;
    },
  },
  actions: {},
  modules: {},
});
