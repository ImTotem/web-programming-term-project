# TasteMap

사용자가 만든 그룹 단위로 함께 쓰는 취향 기반 맛집 지도 PHP 텀프로젝트입니다.

## 실행 위치

이 저장소는 기존 Docker PHP 환경의 `htdocs/tastemap`에 클론되어 있습니다.

- 웹: <http://localhost:8080/tastemap/>
- phpMyAdmin: <http://localhost:8081>

## 초기 설정

1. `config.example.php`를 복사해 `config.php`를 만듭니다.
2. 카카오 개발자 콘솔에서 REST API 키와 JavaScript 키를 발급받아 입력합니다.
3. phpMyAdmin에서 `db/schema.sql`을 `mydb` 데이터베이스에 실행합니다.

## 현재 포함된 것

- 카카오맵 연동 준비가 된 메인 화면
- 카카오 Local API 검색 프록시
- PHP/MySQL 설정 예시
- TasteMap DB 스키마
- 프로젝트 기획 문서

## 프로젝트 구조

```text
.
├── api/place_search.php
├── assets/css/style.css
├── assets/js/app.js
├── db/schema.sql
├── docs/tastemap-design.md
├── includes/bootstrap.php
├── includes/db.php
├── config.example.php
└── index.php
```
