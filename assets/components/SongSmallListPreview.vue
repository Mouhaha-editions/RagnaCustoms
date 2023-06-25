<template>
  <tr>
    <td>
      <div class="d-flex ">
        <a :href="song.url">
          <img :src="song.cover" class="small-cover" alt="cover"/>
        </a>
        <div class="song pl-1">
          <div class="title">
            <a :href="song.url">
              {{ song.fullname }}
            </a>
          </div>
          <div class="author">
            <a :href="song.author.url">
              {{ song.author.fullname }}
            </a>
          </div>
        </div>
      </div>
    </td>
    <td>
      <div class="mapper">
        <a :href="song.mapper.url">
          {{ song.mapper.fullname }}
        </a>
      </div>
      <div class="level-list" v-for="level in song.levels">
        <div :class="['level', level.isRanked ? 'ranked':'']" :style="{'background-color':'#'+level.color}">
          <span>{{ level.rank }}</span>
        </div>
      </div>
    </td>
    <td class="small-col"><UpDownVote :song-id="song.id"></UpDownVote></td>
    <td class="download">
<!--      {% set downloaded = downloadsService.alreadyDownloaded(song) %}-->
      <div class="on-hover">
<!--        <div class="big-buttons d-none">-->
<!--          <a data-no-swup="true" href="ragnac://install/{{ song.id }}" class="one-click"><i class="fas fa-download"></i><br/>1 click</a>-->
<!--          <a data-no-swup="true" href="{{ url("song_direct_download",{id:song.id}) }}" class="ddl"><i class="fas fa-download"></i><br/>ZIP</a>-->
<!--        </div>-->

<!--        <a href="#"-->
<!--           data-toggle="modal"-->
<!--           data-target="#previewSong"-->
<!--           data-refresh="true"-->
<!--           data-url="{{url("partial_preview_song",{id:song.id})}}"-->
<!--        class="ajax-load preview-popup">-->
<!--        <i class="fas fa-play-circle"></i>-->
<!--        </a>-->

        <a href="#"
           class="open-download-buttons text-success ml-1">
          <i class="fas fa-download"></i>
        </a>
      </div>
      <div class="non-hover">
<!--        {% if downloaded %}-->
<!--        <i class="fas fa-check"></i>-->
<!--        {% endif %}-->
      </div>
    </td>
  </tr>
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
      this.$data.song = response.data
    });
  },

  data: function () {
    return {
      song: {
        id: '',
        url: '',
        cover: '',
        allReadyDownloaded: false,
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
            color: '',
            isRanked:false
          }
        ]
      }
    }
  },

}
</script>

<style scoped>

</style>