প্রদত্ত পেজের তথ্যের ওপর ভিত্তি করে **bdbnews.com** ডোমেনটিকে উদাহরণ হিসেবে ব্যবহার করে একটি প্রফেশনাল ডকুমেন্টেশন ফাইল নিচে তৈরি করা হলো:

---

# 🚀 Automated Deployment Documentation

**Domain:** `bdbnews.com`

**Pipeline:** Local Server → GitHub → cPanel (Automated via GitHub Actions)

---

## 📌 System Overview

এই সিস্টেমে লোকাল সার্ভারে কোড পুশ করার সাথে সাথে **GitHub Actions**-এর মাধ্যমে স্বয়ংক্রিয়ভাবে `bdbnews.com` লাইভ সার্ভারে (cPanel) আপডেট হয়ে যাবে।

```
💻 Local Server (code লিখুন) 
       👇 (git push)
🐙 GitHub (Actions workflow trigger) 
       👇 (SSH deploy)
🌐 cPanel / Live Server (auto update ✅)

```

> **প্রতিদিনের কাজের নিয়ম:** কোড পরিবর্তন করুন → `git push origin main` চালান → ৩-৫ মিনিটের মধ্যে লাইভ সাইটে আপডেট সম্পন্ন হবে।

---

## 📋 Setup Checklist

ডিপ্লয়মেন্ট পাইপলাইনটি চালু করতে নিচের ধাপগুলো ক্রমানুসারে সম্পন্ন করুন:

* [ ] GitHub-এ একটি **Private Repository** তৈরি করা।
* [ ] Local প্রজেক্টে Git সেটআপ করে GitHub-এ প্রথম পুশ করা।
* [ ] cPanel-এ **PHP 8.2+** এবং **SSH Access** নিশ্চিত করা।
* [ ] cPanel Terminal থেকে প্রজেক্টের প্রথম ক্লোন/সেটআপ করা।
* [ ] cPanel-এ MySQL Database এবং `.env` ফাইল কনফিগার করা।
* [ ] GitHub-এর Secrets-এ SSH ক্রিডেনশিয়াল যোগ করা।
* [ ] GitHub Actions Workflow রান করে টেস্ট করা।

---

## 🛠️ Step-by-Step Deployment Guide

### Phase 1: GitHub Setup

#### ১. Repository তৈরি

* GitHub-এ গিয়ে একটি নতুন প্রাইভেট রেপোজিটরি তৈরি করুন।
* **Repository Name:** `bdbnews`
* **Visibility:** `Private`

#### ২. Local থেকে GitHub-এ Push

আপনার লোকাল প্রজেক্টের টার্মিনালে নিচের কমান্ডগুলো চালান:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/your-username/bdbnews.git
git push -u origin main

```

> ⚠️ **সতর্কতা:** নিশ্চিত করুন যে `.gitignore` ফাইলে `.env` উল্লেখ আছে, যাতে এটি GitHub-এ পুশ না হয়।

---

### Phase 2: cPanel Server Setup

#### ৩. সার্ভার কনফিগারেশন চেক

* **PHP Version:** cPanel-এর *Select PHP Version* থেকে **8.2 বা তার বেশি** সেট করুন।
* **SSH Access:** cPanel-এর *Security* সেকশন থেকে SSH Access চালু (Enable) করুন।

#### ৪. cPanel Terminal-এ কোড পুল করা

cPanel-এর Terminal অপশনে গিয়ে নিচের কমান্ডগুলো রান করুন:

```bash
# public_html ফোল্ডারে প্রবেশ করুন
cd ~/public_html

# Git ইনিশিয়ালাইজ করে কোড নিয়ে আসুন
git init
git remote add origin https://github.com/your-username/bdbnews.git
git fetch
git reset --hard origin/main

# Composer প্যাকেজ ইনস্টল করুন
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader

```

#### ৫. ডেটাবেস ও এনভায়রনমেন্ট সেটআপ

* cPanel থেকে একটি নতুন MySQL Database ও User তৈরি করে সব পারমিশন দিন।
* টার্মিনালে `.env` ফাইল তৈরি ও কনফিগার করুন:

```bash
cp .env.example .env
nano .env

```

`.env` ফাইলে নিচের তথ্যগুলো পরিবর্তন করুন:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bdbnews.com
DB_DATABASE=cpanel_dbname
DB_USERNAME=cpanel_dbuser
DB_PASSWORD=your_password

```

কনফিগারেশন শেষ করতে নিচের কমান্ডগুলো চালান:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache

```

---

### Phase 3: GitHub Actions Auto-Deployment

#### ৬. GitHub Secrets যোগ করা

GitHub রেপোজিটরির `Settings` -> `Secrets and Variables` -> `Actions` -> `New repository secret`-এ গিয়ে নিচের তথ্যগুলো যোগ করুন:

| Secret Name | Value (মান) |
| --- | --- |
| **SSH_HOST** | আপনার হোস্টিং আইপি বা ডোমেন (`bdbnews.com`) |
| **SSH_USERNAME** | cPanel-এর ইউজারনেম |
| **SSH_PASSWORD** | cPanel-এর পাসওয়ার্ড |
| **SSH_PORT** | সাধারণত `22` বা `22222` |
| **DEPLOY_PATH** | `/home/your_cpanel_user/public_html` |

#### ৭. Workflow ফাইল ও টেস্ট রান

প্রজেক্টের `.github/workflows/deploy.yml` ফাইলে অটো-ডিপ্লয়মেন্ট স্ক্রিপ্টটি সেভ করে পুশ করুন। যেকোনো ছোট পরিবর্তন করে টেস্ট করতে পারেন:

```bash
git add .
git commit -m "Test: auto deployment for bdbnews"
git push origin main

```

পুশ করার পর GitHub-এর **Actions** ট্যাবে গিয়ে লাইভ স্ট্যাটাস (সবুজ টিক ✅ মানে সফল) দেখতে পারবেন।

---

## 🔍 সাধারণ সমস্যা ও সমাধান (Troubleshooting)

* **SSH Connection Refused:** `SSH_PORT` ঠিক আছে কিনা যাচাই করুন। প্রয়োজনে হোস্ٹنگ প্রোভাইডারের সাহায্য নিন।
* **Git Pull Authentication Failed:** cPanel টার্মিনালে Personal Access Token (PAT) ব্যবহার করে রিমোট ইউআরএল সেট করুন:
`git remote set-url origin "[https://YOUR_TOKEN@github.com/user/repo.git](https://YOUR_TOKEN@github.com/user/repo.git)"`
* **Composer: command not found:** গ্লোবাল কম্পোজার কাজ না করলে `php /usr/local/bin/composer install` অথবা `php composer.phar install` ব্যবহার করুন।
* **Storage Symlink Error:** লাইভ সার্ভারে ইমেজ বা ফাইল শো না করলে টার্মিনালে `php artisan storage:link --force` কমান্ডটি চালান।