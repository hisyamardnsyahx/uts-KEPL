<template>
  <div class="wrap">
    <header>
      <div>
        <h1>Inventaris Lab</h1>
        <p class="muted">Pencatatan alat praktikum laboratorium — data tersimpan di database MySQL lewat API Laravel.</p>
      </div>
      <button v-if="token" class="secondary" @click="logout">Logout</button>
    </header>

    <div v-if="pesan" class="ok">{{ pesan }}</div>
    <div v-if="galat" class="error">{{ galat }}</div>

    <!-- LOGIN -->
    <section v-if="!token" class="card">
      <h2>Login</h2>
      <p class="muted">Akun demo dari seeder: <b>admin@lab.test</b> (password ada di README, bukan di kode frontend).</p>
      <form @submit.prevent="login">
        <label>Email
          <input v-model="loginForm.email" type="email" required />
        </label>
        <label>Password
          <input v-model="loginForm.password" type="password" required />
        </label>
        <button type="submit" style="margin-top: 14px">Login</button>
      </form>
    </section>

    <!-- DAFTAR + FORM -->
    <template v-else>
      <section class="card">
        <h2>{{ form.id ? 'Ubah Alat' : 'Tambah Alat' }}</h2>
        <form @submit.prevent="simpan">
          <div class="row">
            <label>Nama alat
              <input v-model="form.name" required maxlength="255" />
            </label>
            <label>Kode (unik, mis. LAB-001)
              <input v-model="form.code" required maxlength="50" />
            </label>
          </div>
          <div class="row">
            <label>Kategori
              <select v-model="form.category" required>
                <option value="">-- pilih --</option>
                <option>Komputer</option>
                <option>Jaringan</option>
                <option>Audio Visual</option>
                <option>Perkakas</option>
                <option>Lainnya</option>
              </select>
            </label>
            <label>Jumlah
              <input v-model.number="form.quantity" type="number" min="0" required />
            </label>
          </div>
          <div class="row">
            <label>Kondisi
              <select v-model="form.condition" required>
                <option value="">-- pilih --</option>
                <option value="baik">Baik</option>
                <option value="rusak_ringan">Rusak ringan</option>
                <option value="rusak_berat">Rusak berat</option>
                <option value="perawatan">Perawatan</option>
              </select>
            </label>
            <label>Lokasi
              <input v-model="form.location" required maxlength="255" placeholder="mis. Lab A" />
            </label>
          </div>
          <label>Deskripsi
            <textarea v-model="form.description" rows="3"></textarea>
          </label>
          <div style="margin-top: 14px">
            <button type="submit">{{ form.id ? 'Simpan perubahan' : 'Tambah alat' }}</button>
            <button v-if="form.id" type="button" class="secondary" @click="resetForm">Batal</button>
          </div>
        </form>
      </section>

      <section class="card">
        <h2>Daftar Alat ({{ items.length }})</h2>
        <button class="secondary" @click="muat">Muat ulang</button>
        <table v-if="items.length">
          <thead>
            <tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jml</th><th>Kondisi</th><th>Lokasi</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <tr v-for="it in items" :key="it.id">
              <td>{{ it.code }}</td>
              <td>{{ it.name }}</td>
              <td>{{ it.category }}</td>
              <td>{{ it.quantity }}</td>
              <td>{{ it.condition }}</td>
              <td>{{ it.location }}</td>
              <td>
                <button class="secondary" @click="edit(it)">Ubah</button>
                <button class="danger" @click="hapus(it)">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="muted">Belum ada data. Tambahkan alat lewat formulir di atas.</p>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from './api'

const token = ref(localStorage.getItem('lab_token') || '')
const items = ref([])
const pesan = ref('')
const galat = ref('')
const loginForm = reactive({ email: '', password: '' })
const kosong = () => ({ id: null, name: '', code: '', category: '', quantity: 0, condition: 'baik', location: '', description: '' })
const form = reactive(kosong())

function tampilkanGalat(e) {
  if (e.response?.status === 401) {
    galat.value = 'Sesi habis / token tidak valid (401). Silakan login ulang.'
    token.value = ''
    localStorage.removeItem('lab_token')
  } else if (e.response?.status === 422) {
    const errs = e.response.data.errors || {}
    galat.value = Object.values(errs).flat().join('\n')
  } else {
    galat.value = e.response?.data?.message || e.message || 'Terjadi kesalahan.'
  }
}

async function login() {
  pesan.value = ''; galat.value = ''
  try {
    const res = await api.post('/login', loginForm)
    token.value = res.data.data.token
    localStorage.setItem('lab_token', token.value)
    pesan.value = `Login berhasil. Selamat datang, ${res.data.data.user.name}.`
    await muat()
  } catch (e) { tampilkanGalat(e) }
}

async function logout() {
  try { await api.post('/logout') } catch { /* abaikan, token tetap dihapus lokal */ }
  token.value = ''
  localStorage.removeItem('lab_token')
  items.value = []
  pesan.value = 'Logout berhasil.'
}

async function muat() {
  galat.value = ''
  try {
    const res = await api.get('/equipments')
    items.value = res.data.data
  } catch (e) { tampilkanGalat(e) }
}

function resetForm() { Object.assign(form, kosong()) }
function edit(it) { Object.assign(form, { ...it }) }

async function simpan() {
  pesan.value = ''; galat.value = ''
  try {
    const payload = { ...form }
    delete payload.id
    if (form.id) {
      await api.put(`/equipments/${form.id}`, payload)
      pesan.value = 'Alat berhasil diubah.'
    } else {
      await api.post('/equipments', payload)
      pesan.value = 'Alat berhasil ditambah (201).'
    }
    resetForm()
    await muat()
  } catch (e) { tampilkanGalat(e) }
}

async function hapus(it) {
  if (!confirm(`Hapus alat "${it.name}" (${it.code})?`)) return
  pesan.value = ''; galat.value = ''
  try {
    await api.delete(`/equipments/${it.id}`)
    pesan.value = 'Alat berhasil dihapus.'
    await muat()
  } catch (e) { tampilkanGalat(e) }
}

onMounted(() => { if (token.value) muat() })
</script>
