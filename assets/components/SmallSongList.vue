<template>

<SongSmallPreview :song-id="songs[0]" ></SongSmallPreview>
  <table class="featured-table table table-sm">
    <tr>
      <th>{{ "Song"|trans }}</th>
      <th>{{ "Mapper"|trans }}</th>
      <th>{{ "Rating"|trans }}</th>
      <th></th>
    </tr>
<SongSmallListPreview v-for="key in songs.length-1"  :song-id="songs[key]"></SongSmallListPreview>
  </table>

</template>

<script>
import SongSmallPreview from "./SongSmallPreview";
import SongSmallListPreview from "./SongSmallListPreview";
import axios from "axios";

export default {
  name: "SmallSongList",
  components: {SongSmallPreview, SongSmallListPreview},
  data: function(){
    return {
      songs : [1872,1873,1874,1875,1876,1877,1878]
    }
  }
}
</script>

<style scoped>

</style>