/**
 * @license <https://github.com/andyvanee/socketeer/blob/master/LICENSE>
 */
import { LitElement, html } from "lit-element"

export class BaseController extends LitElement {
    constructor() {
        super()
        this.connected = false
        this.connect()
    }

    send(action, data, meta) {
        meta = meta || {}
        meta = Object.assign({}, meta, { ts: Math.floor(Date.now() / 1000) })
        const message = JSON.stringify([action, meta, data])

        if (this.connected) {
            this.socket.send(message)
        } else {
            this.on("connected", () => {
                this.socket.send(message)
            })
        }
    }

    on(message, callback) {
        this.addEventListener(message, callback)
    }

    off(message, callback) {
        this.removeEventListener(message, callback)
    }

    trigger(message, data) {
        const evt = new CustomEvent(message, { detail: data })
        this.dispatchEvent(evt)
    }

    connect() {
        const proto = window.location.protocol == "https:" ? "wss" : "ws"
        this.socket = new WebSocket(`${proto}://${window.location.host}/${this.id}`)

        this.socket.addEventListener("message", m => {
            if (m.data == "pong") {
                return
            }
            const message = JSON.parse(m.data)
            const [action, _, data] = message
            if (action) {
                this.trigger(action, data)
            }
        })

        this.socket.addEventListener("open", () => {
            this.connected = true
            this.trigger("connected")
        })

        this.socket.addEventListener("close", () => {
            const status = this.connected ? true : false
            this.connected = false
            if (status) {
                this.trigger("disconnected")
            }
            setTimeout(() => {
                this.connect()
            }, 2500)
        })
    }

    render() {
        return html`
            <p>Base Controller</p>
        `
    }
}
