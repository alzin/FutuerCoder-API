<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Blogs;
use App\Models\Cources;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'firstName' => 'Admin',
            'lastName' => 'User',
            'age' => 30,
            'email' => 'admin@futurecoder.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => Carbon::now(),
        ]);

        // Create sample users for testimonials
        $user1 = User::create([
            'firstName' => 'Sarah',
            'lastName' => 'Ahmed',
            'age' => 12,
            'email' => 'sarah@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => Carbon::now(),
        ]);

        $user2 = User::create([
            'firstName' => 'Omar',
            'lastName' => 'Hassan',
            'age' => 14,
            'email' => 'omar@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => Carbon::now(),
        ]);

        $user3 = User::create([
            'firstName' => 'Lina',
            'lastName' => 'Khalil',
            'age' => 10,
            'email' => 'lina@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => Carbon::now(),
        ]);

        // Create sample blogs
        Blogs::create([
            'title' => 'Why Kids Should Learn Coding Early',
            'description' => 'In today\'s digital world, coding has become an essential skill. Teaching children to code from a young age helps develop critical thinking, problem-solving abilities, and creativity. Studies show that kids who start coding early tend to perform better in mathematics and science. At Future Coder, we believe every child deserves the opportunity to learn these valuable skills in a fun and engaging environment.',
            'ImagePath' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800',
        ]);

        Blogs::create([
            'title' => 'Introduction to Scratch Programming for Beginners',
            'description' => 'Scratch is an excellent visual programming language designed specifically for young learners. It allows kids to create interactive stories, games, and animations by dragging and dropping code blocks. This makes programming accessible and enjoyable for children as young as 7 years old. In this article, we explore how Scratch can be the perfect gateway to the world of programming.',
            'ImagePath' => 'https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800',
        ]);

        Blogs::create([
            'title' => 'The Future of AI and Why Your Child Should Be Ready',
            'description' => 'Artificial Intelligence is transforming every industry, from healthcare to entertainment. By 2030, AI-related jobs are expected to grow exponentially. Preparing your child now with programming skills ensures they will be ready for the future job market. Our courses at Future Coder are designed to introduce kids to AI concepts in age-appropriate ways, building a strong foundation for their future careers.',
            'ImagePath' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800',
        ]);

        Blogs::create([
            'title' => 'Top 5 Programming Languages for Kids in 2024',
            'description' => 'Choosing the right programming language for your child can be overwhelming. Here are the top 5 languages we recommend: 1) Scratch for ages 7-10, 2) Python for ages 10-14, 3) JavaScript for ages 12+, 4) Swift for aspiring app developers, and 5) C++ for advanced young programmers. Each language offers unique benefits and learning opportunities tailored to different age groups and interests.',
            'ImagePath' => 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=800',
        ]);

        // Create sample courses
        Cources::create([
            'title' => 'Scratch for Young Coders',
            'teacher' => 'Ms. Sarah',
            'description' => 'A fun and interactive course designed for young beginners to learn the basics of programming using Scratch. Kids will create games, animations, and stories while learning fundamental coding concepts.',
            'imagePath' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800',
            'price' => 49.99,
            'min_age' => 7,
            'max_age' => 10,
            'course_outline' => 'Week 1: Introduction to Scratch & Basic Movements | Week 2: Loops and Events | Week 3: Variables and Conditions | Week 4: Creating Your First Game | Week 5: Animations and Stories | Week 6: Final Project Presentation',
            'payment_url' => 'https://example.com/pay/scratch',
            'duration_in_session' => 60,
            'course_start_date' => '2026-04-01',
        ]);

        Cources::create([
            'title' => 'Python Programming Fundamentals',
            'teacher' => 'Mr. Ahmed',
            'description' => 'An engaging course that introduces Python programming to young learners. Students will learn variables, loops, functions, and build exciting projects like calculators and simple games.',
            'imagePath' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800',
            'price' => 79.99,
            'min_age' => 10,
            'max_age' => 14,
            'course_outline' => 'Week 1: Introduction to Python | Week 2: Variables and Data Types | Week 3: Conditionals and Loops | Week 4: Functions | Week 5: Lists and Dictionaries | Week 6: Building a Mini Project | Week 7: Object-Oriented Basics | Week 8: Final Project',
            'payment_url' => 'https://example.com/pay/python',
            'duration_in_session' => 90,
            'course_start_date' => '2026-04-15',
        ]);

        Cources::create([
            'title' => 'Web Development with HTML & CSS',
            'teacher' => 'Ms. Lina',
            'description' => 'Learn how to build beautiful websites from scratch! This course covers HTML structure, CSS styling, responsive design, and deploying your first website to the internet.',
            'imagePath' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800',
            'price' => 89.99,
            'min_age' => 12,
            'max_age' => 17,
            'course_outline' => 'Week 1: Introduction to HTML | Week 2: HTML Forms & Tables | Week 3: CSS Basics | Week 4: Flexbox & Grid | Week 5: Responsive Design | Week 6: JavaScript Basics | Week 7: DOM Manipulation | Week 8: Final Website Project',
            'payment_url' => 'https://example.com/pay/webdev',
            'duration_in_session' => 90,
            'course_start_date' => '2026-05-01',
        ]);

        // Create sample testimonials (visible ones)
        Testimonial::create([
            'userId' => $user1->id,
            'description' => 'My daughter absolutely loves the Scratch course! She went from knowing nothing about coding to creating her own games in just a few weeks. The teachers are patient and make learning so much fun!',
            'is_visible' => 1,
            'rating' => 5,
        ]);

        Testimonial::create([
            'userId' => $user2->id,
            'description' => 'The Python course was exactly what my son needed. The instructor explains complex concepts in a way that kids can understand. Highly recommend Future Coder for any parent looking to give their child a head start in technology!',
            'is_visible' => 1,
            'rating' => 5,
        ]);

        Testimonial::create([
            'userId' => $user3->id,
            'description' => 'Great online school with excellent curriculum. My two kids are enrolled in different courses and they look forward to every class. The interactive teaching style keeps them engaged throughout the sessions.',
            'is_visible' => 1,
            'rating' => 4,
        ]);
    }
}
