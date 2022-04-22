<template>
  <nav style="text-align: right">
    <v-btn
      color="primary"
      @click="$router.push({ name: 'connectionForm' })"
      class="py-7"
      >Connection Form</v-btn
    >
    <v-btn
      color="primary"
      @click="$router.push({ name: 'connectionForm' })"
      class="py-7"
      >Load CSV</v-btn
    >
  </nav>

  <div style="height: 50px"></div>
  <v-app>
    <v-container>
      <router-view />
    </v-container>
  </v-app>
</template>

<style lang="scss">
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}

nav {
  padding: 30px;

  a {
    font-weight: bold;
    color: #2c3e50;

    &.router-link-exact-active {
      color: #42b983;
    }
  }
}
</style>
