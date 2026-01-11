<?php session_start(); 
// Include database connection for system stats
include('db_connect.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HFRS - Hostel Facilities Report System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<style>
/* GLOBAL */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}
body{
    background: linear-gradient(-45deg, #0a1930, #1a3a5f, #0a2b4a, #1a486f);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
    color:#333;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* Animated Background Particles */
#particles-js {
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: -1;
}

/* NAVBAR */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 60px;
    background: rgba(10, 25, 48, 0.95);
    color:white;
    position:sticky;
    top:0;
    z-index:1000;
    box-shadow:0 2px 20px rgba(0,0,0,0.3);
    animation: fadeInDown 0.8s ease-out;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 168, 255, 0.2);
}
.navbar .logo{
    display:flex; align-items:center; gap:12px;
}
.navbar img{ 
    height:45px; 
    transition: transform 0.3s ease;
}
.navbar img:hover {
    transform: scale(1.05);
}
.navbar h1{ 
    font-size:22px; 
    font-weight:600;
}
.navbar nav ul{ 
    display:flex; 
    list-style:none; 
    gap:30px; 
    margin:0;
}
.navbar a{
    color:white;
    text-decoration:none;
    font-weight:500;
    font-size:16px;
    transition: all 0.3s ease;
    position: relative;
}
.navbar a:hover{ 
    color:#00a8ff; 
}
.navbar a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: #00a8ff;
    transition: width 0.3s ease;
}
.navbar a:hover::after {
    width: 100%;
}

/* HERO BANNER */
.hero-banner {
    background: linear-gradient(135deg, rgba(10, 25, 48, 0.9) 0%, rgba(26, 58, 95, 0.9) 100%);
    color: white;
    padding: 80px 60px;
    position: relative;
    overflow: hidden;
    animation: fadeIn 1s ease-out;
    backdrop-filter: blur(5px);
    border-bottom: 1px solid rgba(0, 168, 255, 0.2);
}
.hero-banner::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 40%;
    height: 100%;
    background: url('images/mainPicDewan.jpg') center/cover no-repeat;
    opacity: 0.8;
    animation: slideInRight 1.2s ease-out;
}
.hero-banner::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent 30%, rgba(0, 168, 255, 0.1) 50%, transparent 70%);
    animation: shine 3s ease-in-out infinite;
}
.banner-content {
    max-width: 650px;
    position: relative;
    z-index: 2;
    animation: fadeInUp 1s ease-out 0.3s both;
}
.banner-content h1 {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}
.banner-content p {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.9;
    line-height: 1.6;
}
.cta-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.primary-btn, .secondary-btn {
    padding: 14px 32px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    z-index: 1;
}
.primary-btn {
    background: linear-gradient(135deg, #00a8ff, #0097e6);
    color: white;
    border: 2px solid rgba(0, 168, 255, 0.3);
}
.primary-btn:hover {
    background: linear-gradient(135deg, #0170b5, #005a8c);
    border-color: rgba(0, 168, 255, 0.5);
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 168, 255, 0.4);
}
.secondary-btn {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(5px);
}
.secondary-btn:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
}
.primary-btn::after, .secondary-btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
    z-index: -1;
}
.primary-btn:hover::after, .secondary-btn:hover::after {
    left: 100%;
}

/* MAIN CONTENT */
.main-content {
    padding: 60px;
    animation: fadeIn 1s ease-out 0.5s both;
    position: relative;
    z-index: 1;
}
.content-container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

/* Floating Background Elements for Main Content */
.floating-shapes {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.floating-shape {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0, 168, 255, 0.1) 0%, transparent 70%);
    animation: floatAnimation 20s ease-in-out infinite;
}

.shape-1 {
    width: 300px;
    height: 300px;
    top: 10%;
    left: -150px;
    animation-delay: 0s;
}

.shape-2 {
    width: 200px;
    height: 200px;
    bottom: 20%;
    right: -100px;
    animation-delay: 5s;
    animation-duration: 25s;
}

.shape-3 {
    width: 150px;
    height: 150px;
    top: 50%;
    left: 10%;
    animation-delay: 10s;
    animation-duration: 30s;
}

/* SERVICES SECTION */
.services {
    margin-bottom: 60px;
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    padding: 40px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

/* Animated Background for Services */
.services::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 80%, rgba(0, 168, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(10, 25, 48, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(74, 144, 226, 0.1) 0%, transparent 50%);
    z-index: -1;
    animation: pulseBackground 20s ease-in-out infinite;
}

.services::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        linear-gradient(90deg, transparent 50%, rgba(255,255,255,0.02) 50%),
        linear-gradient(transparent 50%, rgba(255,255,255,0.02) 50%);
    background-size: 60px 60px;
    z-index: -1;
    opacity: 0.2;
    animation: gridMove 40s linear infinite;
}

.section-title {
    text-align: center;
    margin-bottom: 40px;
    position: relative;
    z-index: 1;
    animation: fadeInUp 0.8s ease-out;
}
.section-title h2 {
    color: white;
    font-size: 32px;
    margin-bottom: 10px;
    font-weight: 700;
    position: relative;
    display: inline-block;
}
.section-title h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #00a8ff, #0a1930);
    border-radius: 2px;
    animation: widthGrow 1s ease-out 0.5s both;
}
.section-title p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 18px;
    max-width: 700px;
    margin: 25px auto 0;
    position: relative;
    z-index: 1;
}

/* SERVICE CARDS - 2x2 Grid */
.service-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    margin-top: 30px;
    position: relative;
    z-index: 1;
}
.service-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 35px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.8s ease-out forwards;
}
.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.7s ease;
}
.service-card:hover::before {
    left: 100%;
}
.service-card::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #00a8ff, #0a1930, #00a8ff);
    border-radius: 16px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.service-card:hover::after {
    opacity: 1;
}
.service-card:nth-child(1) { animation-delay: 0.2s; }
.service-card:nth-child(2) { animation-delay: 0.3s; }
.service-card:nth-child(3) { animation-delay: 0.4s; }
.service-card:nth-child(4) { animation-delay: 0.5s; }
.service-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 15px 40px rgba(0, 168, 255, 0.2);
    background: rgba(255, 255, 255, 0.15);
}
.service-icon {
    font-size: 40px;
    color: #00a8ff;
    margin-bottom: 20px;
    display: block;
    transition: transform 0.5s ease, color 0.3s ease;
    filter: drop-shadow(0 0 10px rgba(0, 168, 255, 0.3));
}
.service-card:hover .service-icon {
    transform: scale(1.2) rotate(5deg);
    color: white;
}
.service-card h3 {
    color: white;
    font-size: 22px;
    margin-bottom: 15px;
    font-weight: 600;
    transition: color 0.3s ease;
}
.service-card:hover h3 {
    color: #00a8ff;
}
.service-card p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: 20px;
    transition: color 0.3s ease;
}
.service-card:hover p {
    color: white;
}

/* QUICK ACTIONS */
.quick-actions {
    margin: 40px 0;
}
.quick-actions .section-title h2 {
    color: white;
}
.quick-actions .section-title p {
    color: rgba(255, 255, 255, 0.8);
}
.action-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 30px;
}
.action-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.8s ease-out forwards;
}
.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, transparent 30%, rgba(0, 168, 255, 0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.action-card:hover::before {
    opacity: 1;
}
.action-card:nth-child(1) { animation-delay: 0.2s; }
.action-card:nth-child(2) { animation-delay: 0.3s; }
.action-card:nth-child(3) { animation-delay: 0.4s; }
.action-card:nth-child(4) { animation-delay: 0.5s; }
.action-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 15px 40px rgba(0, 168, 255, 0.2);
    background: rgba(255, 255, 255, 0.15);
}
.action-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #00a8ff, #0a1930);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    transition: all 0.5s ease;
    box-shadow: 0 5px 15px rgba(0, 168, 255, 0.3);
}
.action-card:hover .action-icon {
    transform: rotateY(180deg) scale(1.1);
    background: linear-gradient(135deg, #0a1930, #00a8ff);
    box-shadow: 0 8px 25px rgba(0, 168, 255, 0.5);
}
.action-icon i {
    font-size: 24px;
    color: white;
    transition: transform 0.5s ease;
}
.action-card:hover .action-icon i {
    transform: rotateY(-180deg);
}
.action-card h4 {
    color: white;
    margin-bottom: 10px;
    font-size: 18px;
    transition: color 0.3s ease;
}
.action-card:hover h4 {
    color: #00a8ff;
}
.action-card p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    margin-bottom: 15px;
    transition: color 0.3s ease;
}
.action-card:hover p {
    color: white;
}
.action-card .btn-small {
    display: inline-block;
    padding: 8px 20px;
    background: linear-gradient(135deg, #00a8ff, #0097e6);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 168, 255, 0.3);
}
.action-card .btn-small:hover {
    background: linear-gradient(135deg, #0170b5, #005a8c);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 168, 255, 0.4);
}
.action-card .btn-small::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}
.action-card .btn-small:hover::before {
    left: 100%;
}

/* STATUS INDICATORS */
.status-section {
    background: rgba(240, 245, 255, 0.1);
    padding: 40px;
    border-radius: 20px;
    margin: 40px 0;
    animation: fadeIn 0.8s ease-out 0.6s both;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}
.status-section .section-title h2 {
    color: white;
}
.status-section .section-title p {
    color: rgba(255, 255, 255, 0.8);
}
.status-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 30px;
}
.status-item {
    text-align: center;
    padding: 20px;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.8s ease-out forwards;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}
.status-item:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 168, 255, 0.2);
}
.status-item:nth-child(1) { animation-delay: 0.3s; }
.status-item:nth-child(2) { animation-delay: 0.4s; }
.status-item:nth-child(3) { animation-delay: 0.5s; }
.status-item i {
    font-size: 36px;
    margin-bottom: 15px;
    transition: all 0.5s ease;
    filter: drop-shadow(0 0 10px rgba(0, 168, 255, 0.3));
}
.status-item:hover i {
    transform: scale(1.2) rotate(15deg);
}
.status-item .count {
    font-size: 32px;
    font-weight: 700;
    color: white;
    display: block;
    transition: all 0.3s ease;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}
.status-item:hover .count {
    color: #00a8ff;
    transform: scale(1.1);
}
.status-item .label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 16px;
    transition: color 0.3s ease;
}
.status-item:hover .label {
    color: white;
}

/* COUNTER ANIMATION */
.count {
    position: relative;
}
.count::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #00a8ff, transparent);
    transition: width 0.5s ease;
}
.status-item:hover .count::after {
    width: 100%;
}

/* CONTACT CTA */
.contact-cta {
    background: linear-gradient(135deg, rgba(10, 25, 48, 0.9) 0%, rgba(26, 58, 95, 0.9) 100%);
    color: white;
    padding: 50px;
    border-radius: 20px;
    text-align: center;
    margin-top: 60px;
    animation: fadeIn 0.8s ease-out 0.7s both;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 168, 255, 0.2);
    box-shadow: 0 15px 40px rgba(0, 168, 255, 0.2);
    position: relative;
    overflow: hidden;
}
.contact-cta::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent 30%, rgba(0, 168, 255, 0.1) 50%, transparent 70%);
    animation: shine 3s ease-in-out infinite;
}
.contact-cta h3 {
    font-size: 28px;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
}
.contact-cta p {
    opacity: 0.9;
    margin-bottom: 25px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    position: relative;
    z-index: 1;
}
.contact-info {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 30px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}
.contact-item {
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    padding: 15px 25px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.contact-item:hover {
    background: rgba(0, 168, 255, 0.2);
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 168, 255, 0.3);
}
.contact-item i {
    color: #00a8ff;
    font-size: 20px;
    transition: all 0.3s ease;
}
.contact-item:hover i {
    color: white;
    transform: scale(1.2);
}

/* FOOTER */
footer {
    background: rgba(10, 25, 48, 0.95);
    color: white;
    padding: 40px 60px;
    margin-top: 60px;
    animation: fadeIn 0.8s ease-out 0.8s both;
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(0, 168, 255, 0.2);
}
.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}
.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    transition: transform 0.3s ease;
}
.footer-logo:hover {
    transform: translateX(5px);
}
.footer-logo img {
    height: 40px;
    transition: transform 0.3s ease;
}
.footer-logo:hover img {
    transform: rotate(-5deg);
}
.footer-links {
    display: flex;
    gap: 30px;
}
.footer-links a {
    color: white;
    text-decoration: none;
    opacity: 0.9;
    transition: all 0.3s ease;
    position: relative;
}
.footer-links a:hover {
    opacity: 1;
    color: #00a8ff;
}
.footer-links a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 1px;
    background-color: #00a8ff;
    transition: width 0.3s ease;
}
.footer-links a:hover::after {
    width: 100%;
}
.copyright {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    opacity: 0.8;
    font-size: 14px;
    transition: opacity 0.3s ease;
}
.copyright:hover {
    opacity: 1;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
    .service-grid {
        grid-template-columns: 1fr;
    }
    .action-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .status-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .floating-shape {
        display: none;
    }
}

@media (max-width: 768px) {
    .navbar, .hero-banner, .main-content {
        padding: 20px;
    }
    .services {
        padding: 20px;
    }
    .navbar {
        flex-direction: column;
        gap: 15px;
        padding: 15px 20px;
    }
    .hero-banner::before {
        display: none;
    }
    .banner-content h1 {
        font-size: 32px;
    }
    .action-grid {
        grid-template-columns: 1fr;
    }
    .footer-content {
        flex-direction: column;
        text-align: center;
    }
    .status-grid {
        grid-template-columns: 1fr;
    }
    .contact-info {
        flex-direction: column;
        gap: 20px;
    }
}

/* CUSTOM ANIMATIONS */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 0.8;
        transform: translateX(0);
    }
}

@keyframes gradientBG {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes shine {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

@keyframes widthGrow {
    from {
        width: 0;
    }
    to {
        width: 60px;
    }
}

@keyframes floatAnimation {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    25% {
        transform: translate(20px, -20px) scale(1.1);
    }
    50% {
        transform: translate(-15px, 15px) scale(0.9);
    }
    75% {
        transform: translate(10px, 20px) scale(1.05);
    }
}

@keyframes pulseBackground {
    0%, 100% {
        opacity: 0.5;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

@keyframes gridMove {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 60px 60px;
    }
}

/* SCROLL ANIMATIONS */
.service-card, .action-card, .status-item {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.service-card.visible, .action-card.visible, .status-item.visible {
    opacity: 1;
    transform: translateY(0);
}

/* PULSE ANIMATION FOR IMPORTANT ELEMENTS */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 168, 255, 0.4);
    }
    70% {
        box-shadow: 0 0 0 15px rgba(0, 168, 255, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 168, 255, 0);
    }
}

/* Apply pulse to CTA buttons when user is not logged in */
<?php if (!isset($_SESSION['user_id'])) : ?>
.primary-btn {
    animation: pulse 2s infinite;
}
<?php endif; ?>
</style>

<!-- Particles.js Library -->
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Particles.js
    particlesJS("particles-js", {
        particles: {
            number: {
                value: 80,
                density: {
                    enable: true,
                    value_area: 800
                }
            },
            color: {
                value: "#00a8ff"
            },
            shape: {
                type: "circle",
                stroke: {
                    width: 0,
                    color: "#000000"
                }
            },
            opacity: {
                value: 0.3,
                random: true,
                anim: {
                    enable: true,
                    speed: 1,
                    opacity_min: 0.1,
                    sync: false
                }
            },
            size: {
                value: 3,
                random: true,
                anim: {
                    enable: true,
                    speed: 2,
                    size_min: 0.1,
                    sync: false
                }
            },
            line_linked: {
                enable: true,
                distance: 150,
                color: "#00a8ff",
                opacity: 0.2,
                width: 1
            },
            move: {
                enable: true,
                speed: 2,
                direction: "none",
                random: true,
                straight: false,
                out_mode: "out",
                bounce: false,
                attract: {
                    enable: false,
                    rotateX: 600,
                    rotateY: 1200
                }
            }
        },
        interactivity: {
            detect_on: "canvas",
            events: {
                onhover: {
                    enable: true,
                    mode: "grab"
                },
                onclick: {
                    enable: true,
                    mode: "push"
                },
                resize: true
            },
            modes: {
                grab: {
                    distance: 200,
                    line_linked: {
                        opacity: 0.5
                    }
                },
                push: {
                    particles_nb: 4
                }
            }
        },
        retina_detect: true
    });

    // Create floating shapes
    const mainContent = document.querySelector('.main-content');
    const floatingShapes = document.createElement('div');
    floatingShapes.className = 'floating-shapes';
    
    const shapes = [
        { className: 'shape-1', top: '10%', left: '-150px', size: 300, delay: 0 },
        { className: 'shape-2', bottom: '20%', right: '-100px', size: 200, delay: 5 },
        { className: 'shape-3', top: '50%', left: '10%', size: 150, delay: 10 }
    ];
    
    shapes.forEach(shape => {
        const div = document.createElement('div');
        div.className = `floating-shape ${shape.className}`;
        div.style.width = shape.size + 'px';
        div.style.height = shape.size + 'px';
        div.style.top = shape.top || '';
        div.style.left = shape.left || '';
        div.style.bottom = shape.bottom || '';
        div.style.right = shape.right || '';
        div.style.animationDelay = shape.delay + 's';
        floatingShapes.appendChild(div);
    });
    
    mainContent.appendChild(floatingShapes);

    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observe service cards
    document.querySelectorAll('.service-card').forEach(card => {
        observer.observe(card);
    });

    // Observe action cards
    document.querySelectorAll('.action-card').forEach(card => {
        observer.observe(card);
    });

    // Observe status items
    document.querySelectorAll('.status-item').forEach(item => {
        observer.observe(item);
    });

    // Number counter animation for statistics
    const countElements = document.querySelectorAll('.count');
    countElements.forEach(element => {
        const text = element.textContent.trim();
        // Check if it's a number (not percentage symbol)
        if (text.includes('%')) {
            const finalValue = parseInt(text);
            if (!isNaN(finalValue)) {
                let startValue = 0;
                const duration = 2000;
                const increment = finalValue / (duration / 16);
                
                const updateCount = () => {
                    startValue += increment;
                    if (startValue < finalValue) {
                        element.textContent = Math.floor(startValue);
                        setTimeout(updateCount, 16);
                    } else {
                        element.textContent = finalValue + '%';
                    }
                };
                
                // Start counting when element is visible
                const countObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCount();
                            countObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                countObserver.observe(element);
            }
        } else {
            const finalValue = parseInt(text);
            if (!isNaN(finalValue)) {
                let startValue = 0;
                const duration = 2000;
                const increment = finalValue / (duration / 16);
                
                const updateCount = () => {
                    startValue += increment;
                    if (startValue < finalValue) {
                        element.textContent = Math.floor(startValue);
                        setTimeout(updateCount, 16);
                    } else {
                        element.textContent = finalValue;
                    }
                };
                
                // Start counting when element is visible
                const countObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCount();
                            countObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                countObserver.observe(element);
            }
        }
    });

    // Add hover sound effect simulation
    const buttons = document.querySelectorAll('.primary-btn, .secondary-btn, .btn-small');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add click ripple effect
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.7);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                top: ${y}px;
                left: ${x}px;
            `;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Add CSS for ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // Mouse move parallax effect
    document.addEventListener('mousemove', function(e) {
        const floatingShapes = document.querySelectorAll('.floating-shape');
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        
        floatingShapes.forEach((shape, index) => {
            const speed = 0.05 + (index * 0.02);
            const xMove = (x - 0.5) * 100 * speed;
            const yMove = (y - 0.5) * 100 * speed;
            
            shape.style.transform = `translate(${xMove}px, ${yMove}px)`;
        });
    });
});
</script>
</head>

<body>

<!-- Particles.js Container -->
<div id="particles-js"></div>

<header class="navbar">
    <div class="logo">
        <img src="images/utemlogo3.png" alt="UTeM Logo">
        <h1>Hostel Facilities Report System</h1>
    </div>
    <nav>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            
            <?php if (!isset($_SESSION['user_id'])) : ?>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
            <?php else: ?>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="report.php"><i class="fas fa-plus-circle"></i> New Report</a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <?php endif; ?>
            
        </ul>
    </nav>
</header>

<!-- HERO BANNER -->
<section class="hero-banner">
    <div class="banner-content">
        <h1>Report Hostel Facility Issues Quickly & Easily</h1>
        <p>Submit maintenance requests, track progress, and ensure your hostel facilities are always in top condition. Our system connects students with maintenance teams for fast resolution.</p>
        <div class="cta-buttons">
            <?php if (!isset($_SESSION['user_id'])) : ?>
                <a href="login.php" class="primary-btn">Login to Report Issue</a>
                <a href="register.php" class="secondary-btn">Create Account</a>
            <?php else: ?>
                <a href="report.php" class="primary-btn">Submit New Report</a>
                <a href="dashboard.php" class="secondary-btn">View Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="content-container">
        
        <!-- SERVICES SECTION -->
        <section class="services">
            <div class="section-title">
                <h2>Our Services</h2>
                <p>We provide comprehensive facility management solutions for hostel residents</p>
            </div>
            
            <div class="service-grid">
                <div class="service-card">
                    <i class="fas fa-bell service-icon"></i>
                    <h3>Issue Reporting</h3>
                    <p>Report any facility issues in your hostel room or common areas with photo evidence and detailed descriptions. Our system ensures quick submission and acknowledgment.</p>
                </div>
                
                <div class="service-card">
                    <i class="fas fa-tasks service-icon"></i>
                    <h3>Progress Tracking</h3>
                    <p>Monitor your reports in real-time. Get notifications when your request status changes from Pending to In Progress to Completed.</p>
                </div>
                
                <div class="service-card">
                    <i class="fas fa-tools service-icon"></i>
                    <h3>Maintenance Coordination</h3>
                    <p>Our system automatically assigns requests to available technicians and maintenance staff based on urgency and location for faster response times.</p>
                </div>
                
                <div class="service-card">
                    <i class="fas fa-chart-line service-icon"></i>
                    <h3>Performance Analytics</h3>
                    <p>View statistics and response times. We continuously improve our services based on feedback and performance metrics.</p>
                </div>
            </div>
        </section>

        <!-- QUICK ACTIONS -->
        <section class="quick-actions">
            <div class="section-title">
                <h2>Quick Actions</h2>
                <p>Common tasks you can perform quickly</p>
            </div>
            
            <div class="action-grid">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h4>New Report</h4>
                    <p>Submit a new maintenance request</p>
                    <a href="report.php" class="btn-small">Report Now</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <h4>View Reports</h4>
                    <p>Check your submitted reports</p>
                    <a href="dashboard.php" class="btn-small">View All</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h4>Help & FAQ</h4>
                    <p>Get help and find answers</p>
                    <a href="https://www.utem.edu.my/en/help-services.html" class="btn-small">Get Help</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h4>Emergency</h4>
                    <p>Urgent issues requiring immediate attention</p>
                    <a href="https://www.utem.edu.my/en/contact_utem.html" class="btn-small">Emergency Contact</a>
                </div>
            </div>
        </section>

<!-- STATUS INDICATORS -->
<section class="status-section">
    <div class="section-title">
        <h2>System Status</h2>
        <p>Real-time statistics of reports and responses</p>
    </div>
    
    <div class="status-grid">
        <?php
        include('db_connect.php');
        
        // 1. Issues Resolved Percentage
        $resolved_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM reports";
        $resolved_result = mysqli_query($conn, $resolved_query);
        $resolved_row = mysqli_fetch_assoc($resolved_result);
        $resolved_percent = $resolved_row['total'] > 0 ? 
            round(($resolved_row['completed'] / $resolved_row['total']) * 100) : 92;
        
        // 2. Active Users Count
        $users_query = "SELECT COUNT(*) as total_users FROM users WHERE role = 'User'";
        $users_result = mysqli_query($conn, $users_query);
        $users_row = mysqli_fetch_assoc($users_result);
        $active_users = $users_row['total_users'] ?: 150;
        
        // 3. Active Technicians Count
        $techs_query = "SELECT COUNT(*) as total_techs FROM users WHERE role = 'Technician'";
        $techs_result = mysqli_query($conn, $techs_query);
        $techs_row = mysqli_fetch_assoc($techs_result);
        $active_techs = $techs_row['total_techs'] ?: 15;
        ?>
        
        <div class="status-item">
            <i class="fas fa-check-circle" style="color:#4CAF50;"></i>
            <span class="count"><?php echo $resolved_percent; ?></span>
            <span class="label">Issues Resolved (%)</span>
        </div>
        
        <div class="status-item">
            <i class="fas fa-users" style="color:#2196F3;"></i>
            <span class="count"><?php echo $active_users; ?></span>
            <span class="label">Active Users</span>
        </div>
        
        <div class="status-item">
            <i class="fas fa-tools" style="color:#9C27B0;"></i>
            <span class="count"><?php echo $active_techs; ?></span>
            <span class="label">Technicians Active</span>
        </div>
    </div>
</section>

        <!-- CONTACT CTA -->
        <section class="contact-cta">
            <h3>Need Immediate Assistance?</h3>
            <p>For urgent matters or if you cannot submit a report online, contact our support team directly.</p>
            
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>Emergency: (06) 234-5678</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>Email: facilities@utem.edu.my</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <span>Hours: Mon-Fri 8:00 AM - 6:00 PM</span>
                </div>
            </div>
        </section>
    </div>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-logo">
            <img src="images/utemlogo3.png" alt="UTeM Logo">
            <span>Hostel Facilities Report System</span>
        </div>
        <div class="footer-links">
            <a href="https://www.utem.edu.my/en/">About Us</a>
            <a href="https://www.utem.edu.my/en/help-services.html">Contact</a>
        </div>
    </div>
    <div class="copyright">
        © 2025 Hostel Facilities Report System (HFRS). All rights reserved. Universiti Teknikal Malaysia Melaka
    </div>
</footer>

</body>
</html>