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
      // create new object with the eventname as key
      const eventMappings = {};
      for (let i = 0; i < ev.length; i++) {
        eventMappings[ev[i]["eventname"]] = ev[i];
      }
      state.eventMappings = eventMappings;
      // get the unique event categories
      const ec = [];
      for (const key in state.eventMappings) {
        if (
          state.eventMappings[key].newlc &&
          !ec.includes(state.eventMappings[key].newlc)
        ) {
          ec.push(state.eventMappings[key].newlc);
        }
      }
      ec.sort();
      state.eventCategories = ec;
    },
  },
  actions: {},
  modules: {},
});
