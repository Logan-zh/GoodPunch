# Changelog

## v0.1 2026-04-07
1. 企業新增後要能新增人員
2. 新增部門後要能指定人員

## v0.2 2026-04-07
1. 修正新增人員 User Management(create) 無反應
2. 新增視窗的 modal 超出可視範圍

## v0.3 2026-04-07
1. 企業管理人要可以新建部門，部門增加員工

## v1.0 for api branch 2026-04-08
1. 調整無歸屬公司進請假相關功能的流程

## v1.1 2026-04-08
1. 修正 Docker build 階段 SQLite seed 失敗問題，避免把本機 `database.sqlite` 打包進 image，並補上 `pdo_sqlite` 支援
2. 調整本機 Docker Compose 啟動流程，修正 Nginx 轉發與前端資產載入問題，讓本機可透過對應 port 正常開啟系統
3. 修正 admin 在未綁定公司時進入 Leave Approvals 會出現整頁 403，改為導回 dashboard 並顯示提示訊息
4. 修正 admin 在未綁定公司時進入 Attendance 會出現整頁 403，改為導回 dashboard 並顯示提示訊息
5. 改善 dashboard 提示視窗顯示邏輯，確保導頁後的 alert 可穩定彈出，不再因元件初始化時機而漏顯示
