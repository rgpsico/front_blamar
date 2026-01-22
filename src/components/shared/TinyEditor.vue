<template>
  <textarea :id="elementId"></textarea>
</template>

<script>
const DEFAULT_INIT = {
  height: 260,
  menubar: true,
  branding: false,
  plugins:
    'advlist autolink lists link image charmap preview anchor searchreplace ' +
    'visualblocks code fullscreen insertdatetime media table help wordcount',
  toolbar:
    'undo redo | blocks | bold italic underline forecolor | ' +
    'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ' +
    'link image media | removeformat | code fullscreen',
  images_file_types: 'jpg,jpeg,png,webp,gif',
  automatic_uploads: true
}

export default {
  name: 'TinyEditor',
  props: {
    value: {
      type: String,
      default: ''
    },
    init: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      elementId: `tinymce-${Math.random().toString(36).slice(2)}`
    }
  },
  mounted() {
    if (!window.tinymce) {
      return
    }

    const config = Object.assign({}, DEFAULT_INIT, this.init, {
      selector: `#${this.elementId}`,
      setup: editor => {
        editor.on('init', () => {
          editor.setContent(this.value || '')
        })
        editor.on('Change KeyUp', () => {
          this.$emit('input', editor.getContent())
        })
      }
    })

    window.tinymce.init(config)
  },
  watch: {
    value(nextValue) {
      const editor = window.tinymce && window.tinymce.get(this.elementId)
      if (editor && nextValue !== editor.getContent()) {
        editor.setContent(nextValue || '')
      }
    }
  },
  beforeDestroy() {
    const editor = window.tinymce && window.tinymce.get(this.elementId)
    if (editor) {
      editor.destroy()
    }
  }
}
</script>
