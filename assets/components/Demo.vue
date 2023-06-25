<template>
  <div class="container">
    <h1>Ceci est notre composant de démo !</h1>
    <button v-on:click="loadStudentList">Charger la liste des étudiants</button>

    <ul id="studentList">
      <li v-for="student in studentList">{{ student }}</li>
    </ul>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "DemoComponent",
  data: function () {
    return {
      studentList: ['Aucun'],
    }
  },
  methods: {
    loadStudentList(event) {
      // axios.get("/get-student-list").then(response => {
      // });
        this.studentList = ['pierrick','elodie']
    }
  }
};
</script>