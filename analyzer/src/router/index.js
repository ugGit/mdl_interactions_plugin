import { createRouter, createWebHistory } from "vue-router";
import ConnectionFormView from "../views/ConnectionFormView.vue";
import SelectCourseFormView from "../views/SelectCourseFormView.vue";
import SelectEventMappingFileFormView from "../views/SelectEventMappingFileFormView.vue";
import CourseLogAnalyzerView from "../views/CourseLogAnalyzerView.vue";
import store from "@/store";

const routes = [
  {
    path: "/",
    name: "connectionForm",
    component: ConnectionFormView,
  },
  {
    path: "/courses",
    name: "selectCourseForm",
    component: SelectCourseFormView,
  },
  {
    path: "/eventmapper",
    name: "selectEventMappingFileForm",
    component: SelectEventMappingFileFormView,
  },
  {
    path: "/analyze",
    name: "analyzer",
    component: CourseLogAnalyzerView,
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

router.beforeEach(async (to) => {
  // Check if connection data is present and avoid an infinite redirect
  const connectionDataIsPresent =
    store.state.moodleUrl && store.state.moodleToken;
  if (!connectionDataIsPresent && to.name !== "connectionForm") {
    // redirect the user to the login page
    console.log("Connection data not present, redirect to home");
    return { name: "connectionForm" };
  }
});

export default router;
