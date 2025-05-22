<template>
  <div id="modal-template" >
    <transition name="modal">
      <div class="wpruby-modal-mask">
        <div class="wpruby-modal-wrapper" >
          <div class="wpruby-modal-container">
            <div class="wpruby-modal-close"><a href="#" @click="$emit('closed')"><span class="dashicons dashicons-no"></span></a></div>
            <div class="wpruby-modal-header">
              <slot name="header"></slot>
            </div>

            <div class="wpruby-modal-body">
              <slot name="body"></slot>
            </div>

            <div class="wpruby-modal-footer">
              <slot name="footer">
                <button v-if="!noConfirm" @click="$emit('confirmed')" type="button" class="wpruby-modal-button button-primary" style="margin-right: 5px;">
                  {{okText}}
                </button>
                <button v-if="!noClose" @click="$emit('closed')" type="button" class="wpruby-modal-button button-secondary">
                  Close
                </button>
              </slot>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: "Modal",
  data() {
    return {
    }
  },
  props: {
    okText: {
      default: 'Confirm',
      type: String,
    },
    noConfirm: {
      default: false,
      type: Boolean,
    },
    noClose: {
      default: false,
      type: Boolean,
    }
  }
}
</script>

<style scoped>

  .wpruby-modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: table;
    transition: opacity 0.3s ease;
  }

  .wpruby-modal-wrapper {
    display: table-cell;
    vertical-align: middle;
    overflow-y: initial;
  }

  .wpruby-modal-container {
    width:750px;
    height: auto;

    overflow-y: auto;
    margin: 0px auto;
    padding: 20px 30px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    transition: all 0.3s ease;
    font-family: Helvetica, Arial, sans-serif;
  }

  .wpruby-modal-header h3 {
    margin: 0 !important;
    color: #2471b1;
  }

  .wpruby-modal-body {
    margin: 20px 0;
    overflow: scroll;
    max-height: 70vh;
  }

  .wpruby-modal-default-button {
    float: right;
  }

  .wpruby-modal-footer {
    margin-top: 20px;
    clear:both;
  }

  /*
   * The following styles are auto-applied to elements with
   * transition="modal" when their visibility is toggled
   * by Vue.js.
   *
   * You can easily play with the modal transition by editing
   * these styles.
   */

  .wpruby-modal-enter {
    opacity: 0;
  }

  .wpruby-modal-leave-active {
    opacity: 0;
  }

  .wpruby-modal-enter .wpruby-modal-container,
  .wpruby-modal-leave-active .wpruby-modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
  }

  .wpruby-modal-button {
    padding: 0 20px ;
    min-height: 30px;
    font-size: 16px;
  }

  .wpruby-modal-close {
    text-align: right;
  }
  .wpruby-modal-close a{
    text-decoration: none;
  }


</style>
