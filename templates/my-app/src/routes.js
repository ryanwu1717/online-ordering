import React from 'react';
import { Home } from './views/Home';
import { About } from './views/About';
import { NavBar } from './components/NavBar';
import { Routes, Route } from 'react-router-dom';


export const Layout = () => {
  return (
    <div>
      <NavBar />
      <Routes>
      <Route index element={<Home />} />
      <Route path='/About' element={<About/>} />
      </Routes>
    </div>
  );
};