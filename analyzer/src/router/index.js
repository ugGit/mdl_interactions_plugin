import { createRouter, createWebHistory } from "vue-router";
import ConnectionFormView from "../views/ConnectionFormView.vue";
import SelectCourseFormView from "../views/SelectCourseFormView.vue";
import AnalyzerView from "../views/AnalyzerView.vue";

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
    path: "/analyze",
    name: "analyzer",
    component: AnalyzerView,
  },
  {
    path: "/about",
    name: "about",
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: () =>
      import(/* webpackChunkName: "about" */ "../views/AboutView.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

export default router;
