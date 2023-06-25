<template>
  <div class="up_down_vote" id="up_down_vote_2017">
    <div><a style="text-decoration:none" data-toggle="tooltip" title="" class="ajax-link"
            data-success-action="replace" data-replace-selector="#up_down_vote_2017" href="#"
            data-url="/song-vote/upvote/2017" data-original-title="Upvote this song">
      <i class="text-grey fas fa-arrow-up"></i>
      {{ countUp }}
    </a></div>
    <div>
      <a
          v-on:click="downvote"
          data-toggle="tooltip"
          href="#"
          data-url="/song-vote/downvote/2017"
          data-original-title="Downvote this song"
      >
        <i class="text-grey fas fa-arrow-down"></i>
        {{ countDown }}
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: "UpDownVote",
  data: function() {
    return {
      countUp: 0,
      countDown: 0
    }
  }
}
</script>

<style scoped>

</style>