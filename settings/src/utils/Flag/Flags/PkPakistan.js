import * as React from "react";
const SvgPkPakistan = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="PK_-_Pakistan_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#PK_-_Pakistan_svg__a)">
      <path fill="#2F8D00" d="M4 0h12v12H4V0Z" />
      <path fill="#F7FCFF" d="M0 0h4v12H0V0Z" />
      <path
        fill="#F1F9FF"
        d="M11.215 7.653s-2.233.582-4.006-.605c-1.772-1.188-.88-3.924-.88-3.924-.925.134-2.377 3.507-.037 5.199 2.34 1.692 4.582.066 4.923-.67Zm-2.478-3.22-1.186.58 1.251.223.169 1.223.708-1.042 1.395.094-1.092-.814.581-1.088-1.087.498-.808-.76.069 1.086Z"
      />
    </g>
  </svg>
);
export default SvgPkPakistan;
