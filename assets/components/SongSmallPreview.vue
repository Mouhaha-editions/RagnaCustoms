<template>
  <div class="featured-box row" :data-id="songId">
    <div class="col-6 pr-0">

      <figure style="position: relative">
        <a :href="song.href">
          <img :src="song.cover" class="img-fluid shiny" alt="cover">
        </a>
        <div class="level-list">
          <div v-for='level in song.levels'>
            <div class="level" :style="{'background-color':'#'+level.color}">
              <span>{{ level.rank }}</span>
            </div>
          </div>
        </div>
      </figure>
    </div>
    <div class="col-6">
      <div class="title">
        <a :href=song.url itemprop="name">{{ song.fullname }}</a>
      </div>
      <div class="author pb-3">
        <a :href=song.author.url>{{ song.author.fullname }}</a>
      </div>
      <div class="mapper pb-3">
        <a :href=song.mapper.url>
          <span :style=song.mapper.color>
            <i data-toggle="tooltip" title="" class="fas fa-gavel" data-original-title="Premium member"></i>
            {{ song.mapper.fullname }}
          </span></a>
      </div>
    <UpDownVote song-id="{{ songId }}}"></UpDownVote>

      <div class="pt-3">
        <a href="#" data-toggle="modal" data-target="#previewSong" data-refresh="true"
           data-url="https://127.0.0.1:8000/song/partial/preview/2017"
           class="ajax-load btn btn-sm btn-bg-empty btn-warning">
          <i class="fas fa-play-circle"></i> Preview
        </a>
      </div>
      <div class="pt-3 d-flex buttons">

        <div><a data-no-swup="true" href="https://127.0.0.1:8000/songs/ddl/2017"
                class="btn btn-info btn-sm btn-download-zip"><i class="fas fa-download"></i> Zip
        </a></div>
        <div class="ml-2"><a data-no-swup="true" href="ragnac://install/2017"
                             class="btn btn-sm btn-success btn-download-1-click"><i class="fas fa-download"></i> 1 click</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import UpDownVote from "./Tools/UpDownVote";

export default {
  name: "SongSmallPreview",
  components: {UpDownVote},
  props: ['song-id'],
  setup(props) {
    // this.songId = props["song-id"];
  },
  mounted() {
    axios.get("/api/song/details/" + this.songId).then(response => {
      console.log(response.data);
      this.$data.song = response.data
    });
  },

  data: function () {
    return {
      song: {
        id: '',
        url: '',
        cover: '',
        author: {
          fullname: '',
          url: '',
        },
        mapper: {
          fullname: '',
          url: '',
          color: '',
        },
        levels: [
          {
            rank: '',
            color: ''
          }
        ]
      }
    }
  },

}
</script>

<style scoped>

</style>