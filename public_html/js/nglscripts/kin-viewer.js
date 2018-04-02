/// /////////////
// Preferences

NGL.Preferences = function (id, defaultParams) {
  this.signals = {
    keyChanged: new signals.Signal()
  }

  this.id = id || 'ngl-gui'
  var dp = Object.assign({}, defaultParams)

  this.storage = {
    impostor: true,
    quality: 'auto',
    sampleLevel: 0,
    theme: 'dark',
    backgroundColor: 'white',
    overview: true,
    rotateSpeed: 2.0,
    zoomSpeed: 1.2,
    panSpeed: 0.8,
    clipNear: 0,
    clipFar: 100,
    clipDist: 10,
    fogNear: 50,
    fogFar: 100,
    cameraFov: 40,
    cameraType: 'orthographic',
    lightColor: 0xdddddd,
    lightIntensity: 1.0,
    ambientColor: 0xdddddd,
    ambientIntensity: 0.2,
    hoverTimeout: 0
  }

    // overwrite default values with params
  for (var key in this.storage) {
    if (dp[ key ] !== undefined) {
      this.storage[ key ] = dp[ key ]
    }
  }

  try {
    if (window.localStorage[ this.id ] === undefined) {
      window.localStorage[ this.id ] = JSON.stringify(this.storage)
    } else {
      var data = JSON.parse(window.localStorage[ this.id ])
      for (var key in data) {
        this.storage[ key ] = data[ key ]
      }
    }
  } catch (e) {
    NGL.error('localStorage not accessible/available')
  }
}

NGL.Preferences.prototype = {

  constructor: NGL.Preferences,

  getKey: function (key) {
    return this.storage[ key ]
  },

  setKey: function (key, value) {
    this.storage[ key ] = value

    try {
      window.localStorage[ this.id ] = JSON.stringify(this.storage)
      this.signals.keyChanged.dispatch(key, value)
    } catch (e) {
      // Webkit === 22 / Firefox === 1014
      if (e.code === 22 || e.code === 1014) {
        NGL.error('localStorage full')
      } else {
        NGL.error('localStorage not accessible/available', e)
      }
    }
  },

  clear: function () {
    try {
      delete window.localStorage[ this.id ]
    } catch (e) {
      NGL.error('localStorage not accessible/available')
    }
  }

}

NGL.createParameterInput = function (p) {
  if (!p) return

  var input

  if (p.type === 'number') {
    input = new UI.Number(parseFloat(p.value))
      .setRange(p.min, p.max)
      .setPrecision(p.precision)
  } else if (p.type === 'integer') {
    input = new UI.Integer(parseInt(p.value))
      .setRange(p.min, p.max)
  } else if (p.type === 'range') {
    input = new UI.Range(p.min, p.max, p.value, p.step)
      .setValue(parseFloat(p.value))
  } else if (p.type === 'boolean') {
    input = new UI.Checkbox(p.value)
  } else if (p.type === 'text') {
    input = new UI.Input(p.value)
  } else if (p.type === 'select') {
    input = new UI.Select()
      .setWidth('')
      .setOptions(p.options)
      .setValue(p.value)
  } else if (p.type === 'color') {
    input = new UI.ColorPopupMenu(p.label)
      .setValue(p.value)
  } else if (p.type === 'vector3') {
    input = new UI.Vector3(p.value)
      .setPrecision(p.precision)
  } else if (p.type === 'hidden') {

    // nothing to display

  } else {
    console.warn(
      'NGL.createParameterInput: unknown parameter type ' +
      "'" + p.type + "'"
    )
  }

  return input
}

// Sidebar

NGL.SidebarWidget = function (stage) {
  var signals = stage.signals
  var container = new UI.Panel()

  var widgetContainer = new UI.Panel()
    .setClass('Content')

  var compList = []
  var widgetList = []

  signals.componentAdded.add(function (component) {
    var widget

    console.log('log warning in kin-viewer' + component.type)

    switch (component.type) {
      case 'shape':
        widget = new NGL.KinGroupWidget(component, stage)
        break

      default:
        console.warn('NGL.SidebarWidget: component type unknown', component)
        return
    }

    widgetContainer.add(widget)

    compList.push(component)
    widgetList.push(widget)
  })

  signals.componentRemoved.add(function (component) {
    var idx = compList.indexOf(component)

    if (idx !== -1) {
      widgetList[ idx ].dispose()

      compList.splice(idx, 1)
      widgetList.splice(idx, 1)
    }
  })

    // actions

  var expandAll = new UI.Icon('plus-square')
    .setTitle('expand all')
    .setCursor('pointer')
    .onClick(function () {
      widgetList.forEach(function (widget) {
        widget.expand()
      })
    })

  var collapseAll = new UI.Icon('minus-square')
    .setTitle('collapse all')
    .setCursor('pointer')
    .setMarginLeft('10px')
    .onClick(function () {
      widgetList.forEach(function (widget) {
        widget.collapse()
      })
    })

  var centerAll = new UI.Icon('bullseye')
    .setTitle('center all')
    .setCursor('pointer')
    .setMarginLeft('10px')
    .onClick(function () {
      stage.autoView(1000)
    })

  var disposeAll = new UI.DisposeIcon()
    .setMarginLeft('10px')
    .setDisposeFunction(function () {
      stage.removeAllComponents()
    })

  var settingsMenu = new UI.PopupMenu('cogs', 'Settings', 'window')
    .setIconTitle('settings')
    .setMarginLeft('10px')
  settingsMenu.entryLabelWidth = '120px'

    // Busy indicator

  var busy = new UI.Panel()
    .setDisplay('inline')
    .add(
      new UI.Icon('spinner')
        .addClass('spin')
        .setMarginLeft('45px')
    )

  stage.tasks.signals.countChanged.add(function (delta, count) {
    if (count > 0) {
      actions.add(busy)
    } else {
      try {
        actions.remove(busy)
      } catch (e) {
        // already removed
      }
    }
  })

  var paramNames = [
    'clipNear', 'clipFar', 'clipDist', 'fogNear', 'fogFar',
    'lightColor', 'lightIntensity', 'ambientColor', 'ambientIntensity'
  ]

  paramNames.forEach(function (name) {
    var p = stage.parameters[ name ]
    if (p.label === undefined) p.label = name
    var input = NGL.createParameterInput(p)

    if (!input) return

    stage.signals.parametersChanged.add(function (params) {
      input.setValue(params[ name ])
    })

    function setParam () {
      var sp = {}
      sp[ name ] = input.getValue()
      stage.setParameters(sp)
    }

    var ua = navigator.userAgent
    if (p.type === 'range' && !/Trident/.test(ua) && !/MSIE/.test(ua)) {
      input.onInput(setParam)
    } else {
      input.onChange(setParam)
    }

    settingsMenu.addEntry(name, input)
  })

    //

  var actions = new UI.Panel()
    .setClass('Panel Sticky')
    .add(
      expandAll,
      collapseAll,
      centerAll,
      disposeAll,
      settingsMenu
    )

  container.add(
    actions,
    widgetContainer
  )

  return container
}

// Stage

NGL.StageWidget = function (stage) {
  var viewport = new NGL.ViewportWidget(stage).setId('viewport')
  document.body.appendChild(viewport.dom)

  // ensure initial focus on viewer canvas for key-stroke listening
  stage.viewer.renderer.domElement.focus()

  //

  var preferences = new NGL.Preferences('ngl-stage-widget')

  var pp = {}
  for (var name in preferences.storage) {
    pp[ name ] = preferences.getKey(name)
  }
  stage.setParameters(pp)

  //
  var sidebar = new NGL.SidebarWidget(stage).setId('sidebar')
  document.body.appendChild(sidebar.dom)

    //

  var doResizeLeft = false
  var movedResizeLeft = false
  var minResizeLeft = false

  var handleResizeLeft = function (clientX) {
    if (clientX >= 50 && clientX <= window.innerWidth - 10) {
      sidebar.setWidth(window.innerWidth - clientX + 'px')
      viewport.setWidth(clientX + 'px')
      toolbar.setWidth(clientX + 'px')
      stage.handleResize()
    }
    var sidebarWidth = sidebar.dom.getBoundingClientRect().width
    if (clientX === undefined) {
      var mainWidth = window.innerWidth - sidebarWidth
      viewport.setWidth(mainWidth + 'px')
      toolbar.setWidth(mainWidth + 'px')
      stage.handleResize()
    }
    if (sidebarWidth <= 10) {
      minResizeLeft = true
    } else {
      minResizeLeft = false
    }
  }
  handleResizeLeft = NGL.throttle(
    handleResizeLeft, 50, { leading: true, trailing: true }
  )

  var resizeLeft = new UI.Panel()
    .setClass('ResizeLeft')
    .onMouseDown(function () {
      doResizeLeft = true
      movedResizeLeft = false
    })
    .onClick(function () {
      if (minResizeLeft) {
        handleResizeLeft(window.innerWidth - 300)
      } else if (!doResizeLeft && !movedResizeLeft) {
        handleResizeLeft(window.innerWidth - 10)
      }
    })

  sidebar.add(resizeLeft)

  window.addEventListener(
    'mousemove', function (event) {
      if (doResizeLeft) {
        document.body.style.cursor = 'col-resize'
        movedResizeLeft = true
        handleResizeLeft(event.clientX)
      }
    }, false
  )

  window.addEventListener(
    'mouseup', function (event) {
      doResizeLeft = false
      document.body.style.cursor = ''
    }, false
  )

  window.addEventListener(
    'resize', function (event) {
      handleResizeLeft()
    }, false
  )

    //

  this.sidebar = sidebar

  return this
}

// Viewport

NGL.ViewportWidget = function (stage) {
  var viewer = stage.viewer
  var renderer = viewer.renderer

  var container = new UI.Panel()
  container.dom = viewer.container
  container.setPosition('absolute')

    // event handlers

  container.dom.addEventListener('dragover', function (e) {
    e.stopPropagation()
    e.preventDefault()
    e.dataTransfer.dropEffect = 'copy'
  }, false)

  container.dom.addEventListener('drop', function (e) {
    e.stopPropagation()
    e.preventDefault()

    var fn = function (file, callback) {
      stage.loadFile(file, {
        defaultRepresentation: true
      }).then(function () { callback() })
    }
    var queue = new NGL.Queue(fn, e.dataTransfer.files)
  }, false)

  return container
}

NGL.KinGroupWidget = function (component, stage) {
  var signals = component.signals
  var container = new UI.CollapsibleIconPanel('minus-square', 'plus-square')

  var reprContainer = new UI.Panel()

  signals.representationAdded.add(function (repr) {
    reprContainer.add(
      new NGL.RepresentationComponentWidget(repr, stage)
    )
  })

    // Add representation

  var repr = new UI.Select()
    .setColor('#444')
    .setOptions((function () {
      var reprOptions = {
        '': '[ add ]',
        'buffer': 'buffer'
      }
      return reprOptions
    })())
    .onChange(function () {
      component.addRepresentation(repr.getValue())
      repr.setValue('')
      componentPanel.setMenuDisplay('none')
    })

  // Position

  var position = new UI.Vector3()
    .onChange(function () {
      component.setPosition(position.getValue())
    })

    // Rotation

  var q = new NGL.Quaternion()
  var e = new NGL.Euler()
  var rotation = new UI.Vector3()
    .setRange(-6.28, 6.28)
    .onChange(function () {
      e.setFromVector3(rotation.getValue())
      q.setFromEuler(e)
      component.setRotation(q)
    })

  // Scale

  var scale = new UI.Number(1)
    .setRange(0.01, 100)
    .onChange(function () {
      component.setScale(scale.getValue())
    })

  // Matrix

  signals.matrixChanged.add(function () {
    position.setValue(component.position)
    rotation.setValue(e.setFromQuaternion(component.quaternion))
    scale.setValue(component.scale.x)
  })

    // Component panel

  var componentPanel = new UI.ComponentPanel(component)
    .setDisplay('inline-block')
    .setMargin('0px')
    .addMenuEntry('Representation', repr)
    .addMenuEntry(
      'File', new UI.Text(component.shape.path)
                .setMaxWidth('100px')
                .setWordWrap('break-word'))
    .addMenuEntry('Position', position)
    .addMenuEntry('Rotation', rotation)
    .addMenuEntry('Scale', scale)

  // Fill container

  container
    .addStatic(componentPanel)
    .add(reprContainer)

  return container
}

// Representation

NGL.RepresentationComponentWidget = function (component, stage) {
  var signals = component.signals

  var container = new UI.CollapsibleIconPanel('minus-square', 'plus-square')
    .setMarginLeft('20px')

  signals.visibilityChanged.add(function (value) {
    toggle.setValue(value)
  })

  signals.nameChanged.add(function (value) {
    name.setValue(value)
  })

  signals.disposed.add(function () {
    menu.dispose()
    container.dispose()
  })

    // Name

  var name = new UI.EllipsisText(component.name)
    .setWidth('103px')

    // Actions

  var toggle = new UI.ToggleIcon(component.visible, 'eye', 'eye-slash')
    .setTitle('hide/show')
    .setCursor('pointer')
    .setMarginLeft('25px')
    .onClick(function () {
      component.setVisibility(!component.visible)
    })

  var disposeIcon = new UI.DisposeIcon()
    .setMarginLeft('10px')
    .setDisposeFunction(function () {
      component.dispose()
    })

  container
    .addStatic(name)
    .addStatic(toggle)
    .addStatic(disposeIcon)

  // Selection

  if ((component.parent.type === 'structure' ||
          component.parent.type === 'trajectory') &&
        component.repr.selection && component.repr.selection.type === 'selection'
    ) {
    container.add(
      new UI.SelectionPanel(component.repr.selection)
        .setMarginLeft('20px')
        .setInputWidth('194px')
    )
  }

  // Menu

  var menu = new UI.PopupMenu('bars', 'Representation')
    .setMarginLeft('45px')
    .setEntryLabelWidth('130px')

  menu.addEntry('type', new UI.Text(component.repr.type))

    // Parameters

  var repr = component.repr
  var rp = repr.getParameters()

  Object.keys(repr.parameters).forEach(function (name) {
    if (!repr.parameters[ name ]) return
    var p = Object.assign({}, repr.parameters[ name ])
    p.value = rp[ name ]
    if (p.label === undefined) p.label = name
    var input = NGL.createParameterInput(p)

    if (!input) return

    signals.parametersChanged.add(function (params) {
      if (typeof input.setValue === 'function') {
        input.setValue(params[ name ])
      }
    })

    function setParam () {
      var po = {}
      po[ name ] = input.getValue()
      component.setParameters(po)
      component.viewer.requestRender()
    }

    var ua = navigator.userAgent
    if (p.type === 'range' && !/Trident/.test(ua) && !/MSIE/.test(ua)) {
      input.onInput(setParam)
    } else {
      input.onChange(setParam)
    }

    menu.addEntry(name, input)
  })

  container
    .addStatic(menu)

  return container
}
